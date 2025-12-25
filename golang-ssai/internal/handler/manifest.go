package handler

import (
	"context"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"strings"
	"time"

	"github.com/fast-ads-backend/golang-ssai/internal/cache"
	"github.com/fast-ads-backend/golang-ssai/internal/client"
	"github.com/fast-ads-backend/golang-ssai/internal/config"
	"github.com/fast-ads-backend/golang-ssai/internal/models"
	"github.com/fast-ads-backend/golang-ssai/internal/parser"
	"github.com/fast-ads-backend/golang-ssai/internal/service"
	"github.com/gin-gonic/gin"
)

type ManifestHandler struct {
	config          *config.Config
	cache           *cache.RedisCache
	laravelClient   *client.LaravelClient
	parser          *parser.M3U8Parser
	adBreakDetector *service.AdBreakDetector
	vastParser      *parser.VASTParser
}

func NewManifestHandler(cfg *config.Config) *ManifestHandler {
	redisCache := cache.NewRedisCache(cfg)
	laravelClient := client.NewLaravelClient(cfg)
	m3u8Parser := parser.NewM3U8Parser()
	adBreakDetector := service.NewAdBreakDetector()
	vastParser := parser.NewVASTParser()

	return &ManifestHandler{
		config:          cfg,
		cache:           redisCache,
		laravelClient:   laravelClient,
		parser:          m3u8Parser,
		adBreakDetector: adBreakDetector,
		vastParser:      vastParser,
	}
}

// GetManifest handles GET /fast/{tenant}/{channel}.m3u8
func (h *ManifestHandler) GetManifest(c *gin.Context) {
	tenant := c.Param("tenant")
	channel := c.Param("channel")

	// Remove .m3u8 extension from channel if present
	if len(channel) > 5 && channel[len(channel)-5:] == ".m3u8" {
		channel = channel[:len(channel)-5]
	}

	// Get channel info first to check cache with channel config hash
	channelInfo, err := h.laravelClient.GetChannelBySlug(c.Request.Context(), tenant, channel)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"error":   "Failed to get channel information",
			"details": err.Error(),
		})
		return
	}

	// Store tenantID from channelInfo to ensure correct tenant is used
	tenantID := channelInfo.TenantID
	fmt.Printf("DEBUG: GetManifest - tenant slug: %s, channel: %s, tenantID from channel: %d, channelID: %d\n", tenant, channel, tenantID, channelInfo.ID)

	// For live streams, disable caching to ensure always fresh segments
	// Live stream segments expire quickly, so we need to fetch fresh manifest every time
	// Only use cache for ad decision, not for manifest itself
	// Skip cache check for live streams - always fetch fresh manifest
	// This ensures segments are always current

	// Get original manifest URL
	originURL := channelInfo.HLSManifestURL
	if originURL == "" {
		// Fallback to default origin
		originURL = h.getOriginURL(tenant, channel)
	}

	originalManifest, err := h.fetchOriginalManifest(originURL)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"error":      "Failed to fetch original manifest",
			"details":    err.Error(),
			"origin_url": originURL,
		})
		return
	}

	// Rewrite URLs in original manifest first (before parsing)
	rewrittenOriginal := h.rewriteManifestURLs(originalManifest, originURL, c)

	// Check if this is a master playlist (contains #EXT-X-STREAM-INF)
	isMasterPlaylist := strings.Contains(rewrittenOriginal, "#EXT-X-STREAM-INF")

	// If master playlist, fetch the first media playlist
	if isMasterPlaylist {
		fmt.Printf("DEBUG: Detected master playlist, fetching first media playlist...\n")
		mediaPlaylistURL, err := h.extractFirstMediaPlaylistURL(rewrittenOriginal, originURL)
		if err != nil {
			fmt.Printf("ERROR: Failed to extract media playlist URL: %v\n", err)
			// Fallback: return rewritten original manifest
			c.Header("Content-Type", "application/vnd.apple.mpegurl")
			c.Header("Cache-Control", "public, max-age=10")
			c.String(http.StatusOK, rewrittenOriginal)
			return
		}

		fmt.Printf("DEBUG: Fetching media playlist from: %s\n", mediaPlaylistURL)
		mediaManifest, err := h.fetchOriginalManifest(mediaPlaylistURL)
		if err != nil {
			fmt.Printf("ERROR: Failed to fetch media playlist: %v\n", err)
			// Fallback: return rewritten original manifest
			c.Header("Content-Type", "application/vnd.apple.mpegurl")
			c.Header("Cache-Control", "public, max-age=10")
			c.String(http.StatusOK, rewrittenOriginal)
			return
		}

		// Use media playlist instead
		rewrittenOriginal = h.rewriteManifestURLs(mediaManifest, mediaPlaylistURL, c)
		fmt.Printf("DEBUG: Using media playlist (length: %d bytes)\n", len(rewrittenOriginal))
	}

	// Parse manifest
	manifest, err := h.parser.Parse(rewrittenOriginal)
	if err != nil {
		fmt.Printf("ERROR: Failed to parse manifest: %v\n", err)
		fmt.Printf("DEBUG: Manifest preview (first 500 chars):\n%s\n", rewrittenOriginal[:min(500, len(rewrittenOriginal))])
		// Fallback: return rewritten original manifest
		c.Header("Content-Type", "application/vnd.apple.mpegurl")
		c.Header("Cache-Control", "public, max-age=10")
		c.String(http.StatusOK, rewrittenOriginal)
		return
	}

	// Calculate total duration for debug
	var totalDuration float64
	for _, seg := range manifest.Segments {
		totalDuration += seg.Duration
	}
	fmt.Printf("DEBUG: Parsed manifest - segments: %d, total duration: %.2f seconds\n", len(manifest.Segments), totalDuration)

	// Get channel info (already fetched earlier) contains ad_break_interval_seconds
	// Generate static rules from channel config
	staticRules := h.generateStaticRulesFromChannel(channelInfo)

	// tenantID already set from channelInfo above
	channelConfig, err := h.laravelClient.GetChannelConfig(c.Request.Context(), tenantID, channel)
	if err == nil && len(channelConfig.AdRules) > 0 {
		// Add additional rules from channel config
		for _, rule := range channelConfig.AdRules {
			staticRules = append(staticRules, service.StaticAdRule{
				Position: rule.Position,
				Offset:   rule.Offset,
				Duration: rule.Duration,
				Interval: rule.Interval,
			})
		}
	}

	// Detect ad breaks (SCTE-35 + static rules)
	adBreaks := h.adBreakDetector.DetectAdBreaks(manifest, originalManifest, staticRules)
	fmt.Printf("DEBUG: Detected %d ad breaks for channel %s\n", len(adBreaks), channel)

	// Collect all ad breaks with their ads for batch stitching
	adBreaksWithAds := make([]parser.AdBreakWithAds, 0, len(adBreaks))
	for _, adBreak := range adBreaks {
		fmt.Printf("DEBUG: Getting ads for break: %s (position: %s, offset: %.2f)\n", adBreak.ID, adBreak.Position, adBreak.Offset)
		ads, err := h.getAdsForBreak(tenant, channel, adBreak, c)
		if err != nil {
			fmt.Printf("ERROR: Failed to get ads for break %s: %v\n", adBreak.ID, err)
			continue // Skip this break if error
		}
		if len(ads) == 0 {
			fmt.Printf("WARN: No ads returned for break %s (position: %s, offset: %.2f)\n", adBreak.ID, adBreak.Position, adBreak.Offset)
			continue // Skip this break if no ads
		}

		fmt.Printf("DEBUG: Got %d ads for break %s\n", len(ads), adBreak.ID)
		adBreaksWithAds = append(adBreaksWithAds, parser.AdBreakWithAds{
			Offset: adBreak.Offset,
			Ads:    ads,
		})

		// Emit tracking events for impressions (async)
		// Use tenantID from channelInfo to ensure correct tenant
		fmt.Printf("DEBUG: Emitting tracking events - tenantID: %d, channel: %s, ads count: %d\n", tenantID, channel, len(ads))
		go h.emitTrackingEvents(tenantID, channelInfo.ID, channel, ads, c)
	}

	fmt.Printf("DEBUG: Total ad breaks with ads: %d\n", len(adBreaksWithAds))

	// Process VAST URLs to extract HLS manifests and fetch ad segments before stitching
	processedAdBreaks := make([]parser.AdBreakWithAds, 0, len(adBreaksWithAds))
	for _, adBreak := range adBreaksWithAds {
		processedAds := make([]models.Ad, 0, len(adBreak.Ads))
		for _, ad := range adBreak.Ads {
			// If VAST URL doesn't end with .m3u8, fetch VAST and extract HLS manifest
			if !strings.HasSuffix(strings.ToLower(ad.VASTURL), ".m3u8") {
				fmt.Printf("INFO: Processing VAST URL for ad %d: %s\n", ad.AdID, ad.VASTURL)
				vastInfo, err := h.vastParser.ProcessVAST(ad.VASTURL)
				if err != nil {
					fmt.Printf("ERROR: Failed to process VAST for ad %d: %v\n", ad.AdID, err)
					// Skip this ad if VAST processing fails
					continue
				}

				// Extract HLS manifest URL from VAST
				if vastInfo.HLSManifestURL != "" {
					hlsURL := vastInfo.HLSManifestURL
					// Rewrite localhost:8000 to ads.wkkworld.com
					if strings.Contains(hlsURL, "localhost:8000") {
						hlsURL = strings.ReplaceAll(hlsURL, "http://localhost:8000", "https://ads.wkkworld.com")
						fmt.Printf("INFO: Rewrote localhost:8000 to ads.wkkworld.com in HLS URL for ad %d\n", ad.AdID)
					}
					fmt.Printf("INFO: Extracted HLS manifest from VAST for ad %d: %s\n", ad.AdID, hlsURL)

					// Fetch ad manifest and rewrite relative URLs to absolute
					adManifest, err := h.fetchOriginalManifest(hlsURL)
					if err != nil {
						fmt.Printf("ERROR: Failed to fetch ad manifest for ad %d: %v\n", ad.AdID, err)
						continue
					}

					// Rewrite relative URLs in ad manifest to absolute
					adManifestBase := hlsURL
					if lastSlash := strings.LastIndex(adManifestBase, "/"); lastSlash >= 0 {
						adManifestBase = adManifestBase[:lastSlash+1]
					}
					adManifest = h.rewriteManifestURLs(adManifest, hlsURL, c)

					// For ExoPlayer compatibility: convert ad URLs to match origin scheme
					// If origin is HTTP, convert ad HTTPS URLs to HTTP (if possible)
					// This prevents mixed content issues in ExoPlayer
					if strings.HasPrefix(originURL, "http://") {
						// Origin is HTTP, try to convert ad HTTPS URLs to HTTP
						adManifest = strings.ReplaceAll(adManifest, "https://ads.wkkworld.com", "http://ads.wkkworld.com")
						fmt.Printf("INFO: Converted ad URLs to HTTP for ExoPlayer compatibility (origin is HTTP)\n")
					}

					// Store rewritten manifest content in VASTURL (temporary, will be used by stitcher)
					// The original HLS URL is no longer needed since we have the manifest content
					ad.VASTURL = adManifest
				} else if vastInfo.MP4URL != "" {
					// Fallback to MP4 if no HLS
					fmt.Printf("WARN: Ad %d has MP4 URL but no HLS manifest. MP4: %s\n", ad.AdID, vastInfo.MP4URL)
					// For now, skip MP4 ads (we need HLS for SSAI)
					continue
				} else {
					fmt.Printf("ERROR: No video URL found in VAST for ad %d\n", ad.AdID)
					continue
				}
			} else {
				// Already an HLS manifest URL - fetch and rewrite
				fmt.Printf("INFO: Ad %d already has HLS manifest URL: %s\n", ad.AdID, ad.VASTURL)
				adManifest, err := h.fetchOriginalManifest(ad.VASTURL)
				if err != nil {
					fmt.Printf("ERROR: Failed to fetch ad manifest for ad %d: %v\n", ad.AdID, err)
					continue
				}
				adManifest = h.rewriteManifestURLs(adManifest, ad.VASTURL, c)
				// Store rewritten manifest content in VASTURL
				ad.VASTURL = adManifest
			}
			processedAds = append(processedAds, ad)
		}

		if len(processedAds) > 0 {
			processedAdBreaks = append(processedAdBreaks, parser.AdBreakWithAds{
				Offset: adBreak.Offset,
				Ads:    processedAds,
			})
		}
	}

	// Stitch all ad breaks at once (more efficient)
	stitchedManifest := rewrittenOriginal
	if len(processedAdBreaks) > 0 {
		var stitchErr error
		stitchedManifest, stitchErr = h.parser.StitchMultipleAdBreaks(rewrittenOriginal, processedAdBreaks)
		if stitchErr != nil {
			fmt.Printf("ERROR: Failed to stitch ad breaks: %v\n", stitchErr)
			// Log error but return rewritten original manifest
			stitchedManifest = rewrittenOriginal
		}
	}

	// URLs should already be rewritten, but rewrite again to be safe
	rewrittenManifest := h.rewriteManifestURLs(stitchedManifest, originURL, c)

	// Don't cache manifest for live streams - always return fresh
	// This ensures segments are always current and not expired
	// Cache is only used for ad decisions, not for manifest content

	// Return stitched manifest
	c.Header("Content-Type", "application/vnd.apple.mpegurl")
	c.Header("Cache-Control", "no-cache, no-store, must-revalidate")
	c.Header("Pragma", "no-cache")
	c.Header("Expires", "0")
	// CORS headers for HLS players
	c.Header("Access-Control-Allow-Origin", "*")
	c.Header("Access-Control-Allow-Methods", "GET, OPTIONS")
	c.Header("Access-Control-Allow-Headers", "Range")
	c.String(http.StatusOK, rewrittenManifest)
}

func (h *ManifestHandler) getOriginURL(tenant, channel string) string {
	// Fallback: construct URL from tenant and channel
	// This should ideally come from channel config
	return fmt.Sprintf("https://cdn.example.com/hls/%s/%s.m3u8", tenant, channel)
}

func (h *ManifestHandler) fetchOriginalManifest(originURL string) (string, error) {
	// Fetch manifest from origin
	client := &http.Client{
		Timeout: 10 * time.Second,
	}

	req, err := http.NewRequest("GET", originURL, nil)
	if err != nil {
		return "", fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("User-Agent", "FAST-Ads-SSAI/1.0")

	resp, err := client.Do(req)
	if err != nil {
		return "", fmt.Errorf("failed to fetch manifest: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return "", fmt.Errorf("unexpected status %d from origin", resp.StatusCode)
	}

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return "", fmt.Errorf("failed to read response: %w", err)
	}

	return string(body), nil
}

func (h *ManifestHandler) getAdsForBreak(tenant, channel string, adBreak models.AdBreak, c *gin.Context) ([]models.Ad, error) {
	// Get channel info to get tenant ID
	channelInfo, err := h.laravelClient.GetChannelBySlug(c.Request.Context(), tenant, channel)
	if err != nil {
		fmt.Printf("ERROR: Failed to get channel info for %s/%s: %v\n", tenant, channel, err)
		return nil, fmt.Errorf("failed to get channel info: %w", err)
	}
	tenantID := channelInfo.TenantID
	fmt.Printf("DEBUG: getAdsForBreak - tenant: %s, channel: %s, tenantID: %d, adBreak: %s (position: %s, offset: %.2f)\n", tenant, channel, tenantID, adBreak.ID, adBreak.Position, adBreak.Offset)

	// Prepare ad decision request
	req := models.AdDecisionRequest{
		TenantID:        tenantID,
		Channel:         channel,
		AdBreakID:       adBreak.ID,
		Position:        adBreak.Position,
		DurationSeconds: adBreak.Duration,
		Geo:             c.GetHeader("CF-IPCountry"), // Cloudflare header
		Device: func() string {
			device := c.GetHeader("User-Agent")
			if device == "" {
				device = "Unknown"
			}
			// Truncate device string to max 100 characters to avoid Laravel validation error
			if len(device) > 100 {
				device = device[:100]
			}
			return device
		}(),
		Timestamp: time.Now().UTC().Format(time.RFC3339),
	}
	fmt.Printf("DEBUG: Ad decision request: %+v\n", req)

	// Check cache for ad decision
	cacheKey := fmt.Sprintf("ad_decision:%s:%s:%s", tenant, channel, adBreak.ID)
	if cached, err := h.cache.Get(c.Request.Context(), cacheKey); err == nil && cached != "" {
		// Parse cached response (simplified - in production use JSON unmarshaling)
		// For now, skip cache for ad decisions
	}

	// Call Laravel API
	fmt.Printf("DEBUG: Calling Laravel API for ad decision...\n")
	resp, err := h.laravelClient.GetAdDecision(c.Request.Context(), req)
	if err != nil {
		fmt.Printf("ERROR: Laravel API call failed: %v\n", err)
		return nil, err
	}

	fmt.Printf("DEBUG: Laravel API response - Success: %v, Ads count: %d\n", resp.Success, len(resp.Data.Ads))
	if len(resp.Data.Ads) > 0 {
		fmt.Printf("DEBUG: First ad: ID=%d, VASTURL=%s\n", resp.Data.Ads[0].AdID, resp.Data.Ads[0].VASTURL)
	}

	if !resp.Success || len(resp.Data.Ads) == 0 {
		fmt.Printf("WARN: No ads available - Success: %v, Ads count: %d\n", resp.Success, len(resp.Data.Ads))
		return nil, fmt.Errorf("no ads available")
	}

	// Cache ad decision (simplified)
	// h.cache.Set(c.Request.Context(), cacheKey, "", h.config.Cache.AdDecisionTTL)

	fmt.Printf("DEBUG: Returning %d ads for break %s\n", len(resp.Data.Ads), adBreak.ID)
	return resp.Data.Ads, nil
}

// emitTrackingEvents sends tracking events for ad impressions
func (h *ManifestHandler) emitTrackingEvents(tenantID int, channelID int, channel string, ads []models.Ad, c *gin.Context) {
	for _, ad := range ads {
		fmt.Printf("DEBUG: Sending tracking event - tenantID: %d, channelID: %d, adID: %d\n", tenantID, channelID, ad.AdID)
		event := models.TrackingEvent{
			TenantID:   tenantID,
			ChannelID:  channelID,
			AdID:       ad.AdID,
			EventType:  "impression",
			SessionID:  c.Query("session_id"),
			DeviceType: c.GetHeader("User-Agent"),
			GeoCountry: c.GetHeader("CF-IPCountry"),
			IPAddress:  c.ClientIP(),
			UserAgent:  c.Request.UserAgent(),
			Timestamp:  time.Now().UTC().Format(time.RFC3339),
		}

		if err := h.laravelClient.SendTrackingEvent(context.Background(), event); err != nil {
			// Log error but don't block
			fmt.Printf("Failed to send tracking event: %v\n", err)
		}
	}
}

// rewriteManifestURLs rewrites relative URLs in manifest to absolute URLs
// Also rewrites localhost:8000 to ads.wkkworld.com for ad HLS manifests
// Note: We keep HTTP URLs as-is because origin CDN may not support HTTPS
// HLS players (VLC, etc.) typically don't block mixed content like browsers do
func (h *ManifestHandler) rewriteManifestURLs(manifest, originURL string, c *gin.Context) string {
	if originURL == "" {
		fmt.Printf("WARNING: rewriteManifestURLs called with empty originURL\n")
		return manifest
	}

	// Rewrite localhost:8000 to ads.wkkworld.com (for ad HLS manifests)
	manifest = strings.ReplaceAll(manifest, "http://localhost:8000", "https://ads.wkkworld.com")

	// Note: We don't convert HTTP to HTTPS because:
	// 1. Origin CDN may not support HTTPS
	// 2. HLS players (VLC, OTT players) typically don't block mixed content
	// 3. Mixed content (HTTP + HTTPS) is acceptable for HLS streaming

	// Parse origin URL to get base
	originU, err := url.Parse(originURL)
	if err != nil {
		fmt.Printf("ERROR: Failed to parse originURL=%s: %v\n", originURL, err)
		return manifest
	}

	// Get base URL (scheme + host + path up to last slash)
	// Example: http://103.152.36.106/antv/index.m3u8 -> http://103.152.36.106/antv/
	originBase := fmt.Sprintf("%s://%s", originU.Scheme, originU.Host)
	if originU.Path != "" {
		path := originU.Path
		if lastSlash := strings.LastIndex(path, "/"); lastSlash >= 0 {
			originBase += path[:lastSlash+1]
		} else {
			originBase += "/"
		}
	} else {
		originBase += "/"
	}

	lines := strings.Split(manifest, "\n")
	rewritten := make([]string, 0, len(lines))

	for _, line := range lines {
		trimmed := strings.TrimSpace(line)
		originalLine := line

		// Skip comments and empty lines
		if trimmed == "" || strings.HasPrefix(trimmed, "#") {
			rewritten = append(rewritten, originalLine)
			continue
		}

		// Check if line is a URL (not starting with # and not already absolute)
		if !strings.Contains(trimmed, "://") {
			// Relative URL - make it absolute using origin base
			if strings.HasPrefix(trimmed, "/") {
				// Absolute path - use origin domain
				absoluteURL := fmt.Sprintf("%s://%s%s", originU.Scheme, originU.Host, trimmed)
				rewritten = append(rewritten, absoluteURL)
			} else {
				// Relative path - append to origin base
				absoluteURL := originBase + trimmed
				rewritten = append(rewritten, absoluteURL)
			}
		} else {
			// Already absolute URL - keep as is
			// Note: We keep HTTP URLs as-is because origin CDN may not support HTTPS
			// HLS players don't block mixed content like browsers do
			rewritten = append(rewritten, originalLine)
		}
	}

	return strings.Join(rewritten, "\n")
}

// generateStaticRulesFromChannel generates static ad rules from channel configuration
func (h *ManifestHandler) generateStaticRulesFromChannel(channelInfo *models.ChannelInfo) []service.StaticAdRule {
	rules := []service.StaticAdRule{}

	// For ExoPlayer compatibility: skip pre-roll entirely
	// Pre-roll at any early offset causes ExoPlayer to skip or fail to play
	// Instead, we'll start mid-rolls from the interval itself
	// If EnablePreRoll is true, we'll just ensure first mid-roll happens early (but not too early)

	// Note: We skip pre-roll completely for ExoPlayer compatibility
	// User can still get ads, but they'll start from the interval (e.g., 60 seconds)
	if channelInfo.EnablePreRoll {
		fmt.Printf("DEBUG: Pre-roll requested but skipped for ExoPlayer compatibility\n")
		fmt.Printf("DEBUG: Ads will start from interval instead\n")
	}

	// Generate mid-roll breaks based on interval
	if channelInfo.AdBreakIntervalSeconds > 0 {
		interval := float64(channelInfo.AdBreakIntervalSeconds)

		// Start from the interval itself (e.g., 60 seconds)
		// This ensures ExoPlayer compatibility - no ads too early
		startOffset := interval

		rules = append(rules, service.StaticAdRule{
			Position: "mid-roll",
			Offset:   startOffset,                                 // First interval mid-roll
			Duration: 30,                                          // Default 30 seconds (Laravel requires >= 1)
			Interval: float64(channelInfo.AdBreakIntervalSeconds), // Repeat every interval seconds
		})
		fmt.Printf("DEBUG: Generated interval mid-rolls every %.0f seconds (starting from %.0fs)\n",
			interval, startOffset)
	} else if channelInfo.EnablePreRoll {
		// If interval is 0 but pre-roll is enabled, add a default mid-roll at 60 seconds
		// This ensures ads still appear even without interval configured
		rules = append(rules, service.StaticAdRule{
			Position: "mid-roll",
			Offset:   60.0, // Default 60 seconds
			Duration: 30,   // Default 30 seconds
			Interval: 0,    // No repeat
		})
		fmt.Printf("DEBUG: Generated default mid-roll at 60 seconds (no interval, pre-roll enabled)\n")
	}

	return rules
}

// extractFirstMediaPlaylistURL extracts the first media playlist URL from a master playlist
func (h *ManifestHandler) extractFirstMediaPlaylistURL(masterPlaylist, baseURL string) (string, error) {
	lines := strings.Split(masterPlaylist, "\n")

	for i, line := range lines {
		line = strings.TrimSpace(line)
		if strings.HasPrefix(line, "#EXT-X-STREAM-INF:") {
			// Next non-empty, non-comment line should be the URL
			for j := i + 1; j < len(lines); j++ {
				nextLine := strings.TrimSpace(lines[j])
				if nextLine != "" && !strings.HasPrefix(nextLine, "#") {
					// Found the URL
					if strings.Contains(nextLine, "://") {
						// Absolute URL
						return nextLine, nil
					} else {
						// Relative URL - make it absolute
						baseU, err := url.Parse(baseURL)
						if err != nil {
							return "", fmt.Errorf("failed to parse base URL: %w", err)
						}

						// Resolve relative URL
						resolvedURL, err := baseU.Parse(nextLine)
						if err != nil {
							return "", fmt.Errorf("failed to resolve URL: %w", err)
						}

						return resolvedURL.String(), nil
					}
				}
			}
		}
	}

	return "", fmt.Errorf("no media playlist URL found in master playlist")
}

func min(a, b int) int {
	if a < b {
		return a
	}
	return b
}
