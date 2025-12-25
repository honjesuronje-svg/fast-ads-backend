package parser

import (
	"fmt"
	"sort"
	"strings"

	"github.com/fast-ads-backend/golang-ssai/internal/models"
	"github.com/fast-ads-backend/golang-ssai/pkg/hls"
)

type M3U8Parser struct{}

func NewM3U8Parser() *M3U8Parser {
	return &M3U8Parser{}
}

// Parse parses an M3U8 manifest string into a Manifest struct
func (p *M3U8Parser) Parse(manifest string) (*models.Manifest, error) {
	hlsManifest, err := hls.ParseManifest(manifest)
	if err != nil {
		return nil, fmt.Errorf("failed to parse manifest: %w", err)
	}

	// Convert to internal model
	m := &models.Manifest{
		Version:       hlsManifest.Version,
		PlaylistType:   hlsManifest.PlaylistType,
		TargetDuration: int(hlsManifest.TargetDuration),
		MediaSequence:  hlsManifest.MediaSequence,
		Segments:       []models.Segment{},
		AdBreaks:       []models.AdBreak{},
	}

	for _, seg := range hlsManifest.Segments {
		m.Segments = append(m.Segments, models.Segment{
			URI:           seg.URI,
			Duration:      seg.Duration,
			Title:         seg.Title,
			Discontinuity: seg.Discontinuity,
		})
	}

	return m, nil
}

// DetectSCTE35 detects SCTE-35 cues in manifest
func (p *M3U8Parser) DetectSCTE35(manifest string) ([]models.AdBreak, error) {
	// This is now handled by AdBreakDetector service
	// Keeping for backward compatibility
	lines := strings.Split(manifest, "\n")
	adBreaks := []models.AdBreak{}

	for i, line := range lines {
		if strings.Contains(line, "#EXT-X-CUE-OUT") {
			adBreaks = append(adBreaks, models.AdBreak{
				ID:       fmt.Sprintf("scte35_%d", i),
				Position: "mid-roll",
				Type:     "scte35",
			})
		}
	}

	return adBreaks, nil
}

// StitchAds stitches ads into manifest at specified break point
// This handles a single ad break insertion
func (p *M3U8Parser) StitchAds(manifest string, ads []models.Ad, breakPoint float64) (string, error) {
	hlsManifest, err := hls.ParseManifest(manifest)
	if err != nil {
		return manifest, fmt.Errorf("failed to parse manifest: %w", err)
	}

	// Find insertion point based on cumulative duration
	insertIndex := p.findInsertionPoint(hlsManifest, breakPoint)
	if insertIndex < 0 {
		return manifest, fmt.Errorf("invalid insertion point: %.2f", breakPoint)
	}

	// Convert ads to ad segments
	adSegments := make([]hls.AdSegment, 0, len(ads))
	for _, ad := range ads {
		adSegments = append(adSegments, hls.AdSegment{
			URI:      ad.VASTURL,
			Duration: float64(ad.DurationSeconds),
			Title:    fmt.Sprintf("Ad %d", ad.AdID),
		})
	}

	// Insert ad segments
	if err := hls.InsertAdSegments(hlsManifest, adSegments, insertIndex); err != nil {
		return manifest, fmt.Errorf("failed to insert ads: %w", err)
	}

	// Render back to M3U8
	return hls.RenderManifest(hlsManifest), nil
}

// StitchMultipleAdBreaks stitches multiple ad breaks into manifest
// This is more efficient than calling StitchAds multiple times
func (p *M3U8Parser) StitchMultipleAdBreaks(manifest string, adBreaks []AdBreakWithAds) (string, error) {
	fmt.Printf("DEBUG: StitchMultipleAdBreaks called with %d ad breaks\n", len(adBreaks))
	hlsManifest, err := hls.ParseManifest(manifest)
	if err != nil {
		return manifest, fmt.Errorf("failed to parse manifest: %w", err)
	}
	fmt.Printf("DEBUG: Parsed manifest - %d segments before stitching\n", len(hlsManifest.Segments))

	// Sort ad breaks by offset (ascending)
	sort.Slice(adBreaks, func(i, j int) bool {
		return adBreaks[i].Offset < adBreaks[j].Offset
	})

	// Calculate total duration of manifest
	var totalDuration float64
	for _, seg := range hlsManifest.Segments {
		totalDuration += seg.Duration
	}
	fmt.Printf("DEBUG: Manifest total duration: %.2f seconds, %d segments\n", totalDuration, len(hlsManifest.Segments))
	
	// Process ad breaks in reverse order to maintain correct indices
	// We need to insert from end to beginning to avoid index shifting issues
	for i := len(adBreaks) - 1; i >= 0; i-- {
		adBreak := adBreaks[i]
		
		fmt.Printf("DEBUG: Processing ad break: offset=%.2f\n", adBreak.Offset)
		
		// Skip ad breaks that are beyond the manifest duration
		// For LIVE streams, we should only insert ads within the current manifest window
		if adBreak.Offset > totalDuration {
			fmt.Printf("DEBUG: Skipping ad break at offset %.2f (beyond manifest duration %.2f)\n", 
				adBreak.Offset, totalDuration)
			continue
		}
		
		// Special handling for pre-roll (offset = 0)
		// Pre-roll must be inserted BEFORE the first segment (at index 0)
		var insertIndex int
		if adBreak.Offset == 0 {
			// Pre-roll: always insert at index 0 (before first segment)
			insertIndex = 0
		} else {
			insertIndex = p.findInsertionPoint(hlsManifest, adBreak.Offset)
			if insertIndex < 0 {
				fmt.Printf("DEBUG: Skipping ad break at offset %.2f (invalid insertion point)\n", adBreak.Offset)
				continue // Skip invalid insertion points
			}
		}

		// Convert ads to ad segments
		// Note: ad.VASTURL now contains the ad manifest content (not URL) with rewritten absolute URLs
		adSegments := make([]hls.AdSegment, 0, len(adBreak.Ads))
		for _, ad := range adBreak.Ads {
			// ad.VASTURL contains the ad manifest content with absolute URLs
			// Parse it and extract segments
			// ad.VASTURL now contains the rewritten ad manifest with absolute URLs
			fmt.Printf("DEBUG: Parsing ad manifest for ad %d (length: %d chars)\n", ad.AdID, len(ad.VASTURL))
			adManifest, err := hls.ParseManifest(ad.VASTURL)
			if err != nil {
				fmt.Printf("ERROR: Failed to parse ad manifest for ad %d: %v\n", ad.AdID, err)
				continue
			}
			
			fmt.Printf("DEBUG: Ad %d manifest parsed - %d segments found\n", ad.AdID, len(adManifest.Segments))
			
			// Add each segment from ad manifest as a separate ad segment
			for i, seg := range adManifest.Segments {
				fmt.Printf("DEBUG: Ad %d segment %d: URI=%s, Duration=%.2f\n", ad.AdID, i, seg.URI, seg.Duration)
				adSegments = append(adSegments, hls.AdSegment{
					URI:      seg.URI,
					Duration: seg.Duration,
					Title:    fmt.Sprintf("Ad %d", ad.AdID),
				})
			}
		}

		// Insert ad segments
		fmt.Printf("DEBUG: Inserting %d ad segments at index %d\n", len(adSegments), insertIndex)
		if err := hls.InsertAdSegments(hlsManifest, adSegments, insertIndex); err != nil {
			fmt.Printf("ERROR: Failed to insert ad segments at index %d: %v\n", insertIndex, err)
			// Log error but continue with other breaks
			continue
		}
		fmt.Printf("DEBUG: Successfully inserted ad segments. Manifest now has %d segments\n", len(hlsManifest.Segments))
	}

	// Render back to M3U8
	rendered := hls.RenderManifest(hlsManifest)
	fmt.Printf("DEBUG: Rendered manifest - %d bytes\n", len(rendered))
	return rendered, nil
}

// AdBreakWithAds represents an ad break with its associated ads
type AdBreakWithAds struct {
	Offset float64
	Ads    []models.Ad
}

// findInsertionPoint finds the segment index where to insert ads based on cumulative duration
func (p *M3U8Parser) findInsertionPoint(hlsManifest *hls.Manifest, breakPoint float64) int {
	var cumulativeDuration float64

	for i, seg := range hlsManifest.Segments {
		cumulativeDuration += seg.Duration
		if cumulativeDuration >= breakPoint {
			return i
		}
	}

	// If break point is beyond all segments, return -1 to skip
	// This prevents inserting ads at the end of manifest which would cause playback to stop
	return -1
}
