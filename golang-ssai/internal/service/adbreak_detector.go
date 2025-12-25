package service

import (
	"fmt"
	"regexp"
	"strconv"
	"strings"

	"github.com/fast-ads-backend/golang-ssai/internal/models"
	"github.com/fast-ads-backend/golang-ssai/pkg/scte35"
)

// AdBreakDetector detects ad breaks in HLS manifests
type AdBreakDetector struct{}

func NewAdBreakDetector() *AdBreakDetector {
	return &AdBreakDetector{}
}

// DetectAdBreaks detects all ad breaks in a manifest
// Priority: SCTE-35 > Static Rules > Default Rules
func (d *AdBreakDetector) DetectAdBreaks(manifest *models.Manifest, manifestText string, staticRules []StaticAdRule) []models.AdBreak {
	adBreaks := []models.AdBreak{}

	// 1. Try SCTE-35 detection first
	scte35Breaks := d.detectSCTE35(manifestText)
	if len(scte35Breaks) > 0 {
		adBreaks = append(adBreaks, scte35Breaks...)
	}

	// 2. Apply static rules if no SCTE-35 found or as fallback
	if len(adBreaks) == 0 || len(staticRules) > 0 {
		staticBreaks := d.detectStaticRules(manifest, staticRules)
		adBreaks = append(adBreaks, staticBreaks...)
	}

	// 3. Remove duplicates and sort by offset
	adBreaks = d.deduplicateAndSort(adBreaks)

	return adBreaks
}

// detectSCTE35 detects SCTE-35 cues in manifest
func (d *AdBreakDetector) detectSCTE35(manifestText string) []models.AdBreak {
	adBreaks := []models.AdBreak{}
	lines := strings.Split(manifestText, "\n")

	var currentOffset float64
	var cueOutFound bool
	var cueOutOffset float64
	var cueDuration int

	for i, line := range lines {
		line = strings.TrimSpace(line)

		// Check for #EXT-X-CUE-OUT
		if strings.Contains(line, "#EXT-X-CUE-OUT") {
			cueOutFound = true
			cueOutOffset = currentOffset

			// Try to extract duration from tag
			// Format: #EXT-X-CUE-OUT:30 or #EXT-X-CUE-OUT-CONT:30/30
			durationRegex := regexp.MustCompile(`(\d+)`)
			matches := durationRegex.FindStringSubmatch(line)
			if len(matches) > 0 {
				if dur, err := strconv.Atoi(matches[1]); err == nil {
					cueDuration = dur
				}
			}
		}

		// Check for #EXT-X-CUE-IN
		if strings.Contains(line, "#EXT-X-CUE-IN") && cueOutFound {
			adBreaks = append(adBreaks, models.AdBreak{
				ID:       fmt.Sprintf("scte35_%d", i),
				Position: "mid-roll",
				Offset:   cueOutOffset,
				Duration: cueDuration,
				Type:     "scte35",
			})
			cueOutFound = false
			cueDuration = 0
		}

		// Check for #EXT-X-SCTE35 tag
		if strings.HasPrefix(line, "#EXT-X-SCTE35:") {
			scte35Data := strings.TrimPrefix(line, "#EXT-X-SCTE35:")
			cue, err := scte35.ParseSCTE35(scte35Data)
			if err == nil && cue != nil {
				adBreaks = append(adBreaks, models.AdBreak{
					ID:       fmt.Sprintf("scte35_%d", i),
					Position: "mid-roll",
					Offset:   currentOffset,
					Duration: cue.BreakDuration,
					Type:     "scte35",
				})
			}
		}

		// Track cumulative duration from #EXTINF tags
		if strings.HasPrefix(line, "#EXTINF:") {
			// Extract duration from #EXTINF:10.5,title
			parts := strings.Split(line, ",")
			if len(parts) > 0 {
				durationStr := strings.TrimPrefix(parts[0], "#EXTINF:")
				if duration, err := strconv.ParseFloat(durationStr, 64); err == nil {
					currentOffset += duration
				}
			}
		}
	}

	return adBreaks
}

// StaticAdRule represents a static ad break rule
type StaticAdRule struct {
	Position string  // pre-roll, mid-roll, post-roll
	Offset   float64 // seconds from start (for mid-roll) or end (for post-roll)
	Duration int     // expected duration in seconds
	Interval float64 // repeat interval in seconds (for multiple mid-rolls)
}

// detectStaticRules detects ad breaks based on static rules
func (d *AdBreakDetector) detectStaticRules(manifest *models.Manifest, rules []StaticAdRule) []models.AdBreak {
	adBreaks := []models.AdBreak{}

	// Calculate total duration
	totalDuration := d.calculateTotalDuration(manifest)

	for _, rule := range rules {
		switch rule.Position {
		case "pre-roll":
			// Pre-roll always at offset 0, regardless of manifest type (master or media)
			adBreaks = append(adBreaks, models.AdBreak{
				ID:       "pre_roll_1",
				Position: "pre-roll",
				Offset:   0,
				Duration: rule.Duration,
				Type:     "static",
			})
			fmt.Printf("DEBUG: Generated pre-roll ad break (offset: 0, duration: %d)\n", rule.Duration)

		case "mid-roll":
			if rule.Interval > 0 {
				// Multiple mid-rolls at intervals
				// For LIVE streams: only generate ad breaks within manifest duration
				// If interval is larger than manifest duration, insert ad at middle of manifest
				maxAdBreaks := 5 // Maximum number of ad breaks to generate
				
				// For LIVE streams (short manifest), adjust strategy
				// LIVE streams typically have 3-4 segments (15-24 seconds)
				// Best practice: For LIVE streams, interval should be much smaller than manifest duration
				// OR we insert ad at a fixed position (e.g., after first 2-3 segments)
				if totalDuration < 300 {
					// LIVE stream: check if interval start is larger than manifest duration
					if rule.Offset >= totalDuration {
						// Interval is too large for current manifest window
						// For LIVE streams: insert ad after first few segments (e.g., at 12-15 seconds)
						// This ensures ad appears early enough in the window
						adOffset := totalDuration * 0.5 // 50% of manifest duration
						if adOffset < 12 {
							adOffset = 12 // Minimum 12 seconds for ExoPlayer compatibility
						}
						if adOffset >= totalDuration {
							adOffset = totalDuration * 0.75 // Use 75% if 50% is too close to end
						}
						
						adBreaks = append(adBreaks, models.AdBreak{
							ID:       fmt.Sprintf("mid_roll_%.0f", adOffset),
							Position: "mid-roll",
							Offset:   adOffset,
							Duration: rule.Duration,
							Type:     "static",
						})
						fmt.Printf("DEBUG: LIVE stream - Interval (%.0fs) >= manifest duration (%.0fs), inserting ad at %.0fs (%.0f%% of duration)\n",
							rule.Offset, totalDuration, adOffset, (adOffset/totalDuration)*100)
					} else {
						// Interval fits within manifest - generate ad breaks normally
						// But limit to only 1-2 ad breaks for LIVE streams to avoid manifest bloat
						maxDuration := totalDuration
						maxAdBreaks = 2 // Limit to 2 ad breaks for LIVE streams
						
						adBreakCount := 0
						for offset := rule.Offset; offset < maxDuration && adBreakCount < maxAdBreaks; offset += rule.Interval {
							adBreaks = append(adBreaks, models.AdBreak{
								ID:       fmt.Sprintf("mid_roll_%.0f", offset),
								Position: "mid-roll",
								Offset:   offset,
								Duration: rule.Duration,
								Type:     "static",
							})
							adBreakCount++
						}
						fmt.Printf("DEBUG: LIVE stream - Generated %d mid-roll ad breaks (interval: %.0fs, max duration: %.0fs, limit: %d)\n",
							adBreakCount, rule.Interval, maxDuration, maxAdBreaks)
					}
				} else {
					// VOD stream: generate based on duration
					maxDuration := totalDuration
					adBreakCount := 0
					for offset := rule.Offset; offset < maxDuration && adBreakCount < maxAdBreaks; offset += rule.Interval {
						adBreaks = append(adBreaks, models.AdBreak{
							ID:       fmt.Sprintf("mid_roll_%.0f", offset),
							Position: "mid-roll",
							Offset:   offset,
							Duration: rule.Duration,
							Type:     "static",
						})
						adBreakCount++
					}
					fmt.Printf("DEBUG: Generated %d mid-roll ad breaks (interval: %.0fs, max duration: %.0fs, limit: %d)\n",
						adBreakCount, rule.Interval, maxDuration, maxAdBreaks)
				}
			} else {
				// Single mid-roll - only add if within reasonable range (10 minutes)
				if rule.Offset <= 600 || rule.Offset <= totalDuration {
					adBreaks = append(adBreaks, models.AdBreak{
						ID:       fmt.Sprintf("mid_roll_%.0f", rule.Offset),
						Position: "mid-roll",
						Offset:   rule.Offset,
						Duration: rule.Duration,
						Type:     "static",
					})
				}
			}

		case "post-roll":
			postRollOffset := totalDuration - rule.Offset
			if postRollOffset < 0 {
				postRollOffset = 0
			}
			adBreaks = append(adBreaks, models.AdBreak{
				ID:       "post_roll_1",
				Position: "post-roll",
				Offset:   postRollOffset,
				Duration: rule.Duration,
				Type:     "static",
			})
		}
	}

	return adBreaks
}

// calculateTotalDuration calculates total duration of all segments
func (d *AdBreakDetector) calculateTotalDuration(manifest *models.Manifest) float64 {
	var total float64
	for _, seg := range manifest.Segments {
		total += seg.Duration
	}
	return total
}

// deduplicateAndSort removes duplicate ad breaks and sorts by offset
func (d *AdBreakDetector) deduplicateAndSort(adBreaks []models.AdBreak) []models.AdBreak {
	if len(adBreaks) == 0 {
		return adBreaks
	}

	// Remove duplicates based on offset (within 1 second tolerance)
	unique := []models.AdBreak{}
	seen := make(map[int]bool) // Track seen offsets (rounded to int)

	for _, break_ := range adBreaks {
		offsetKey := int(break_.Offset)
		if !seen[offsetKey] {
			seen[offsetKey] = true
			unique = append(unique, break_)
		}
	}

	// Sort by offset
	for i := 0; i < len(unique)-1; i++ {
		for j := i + 1; j < len(unique); j++ {
			if unique[i].Offset > unique[j].Offset {
				unique[i], unique[j] = unique[j], unique[i]
			}
		}
	}

	return unique
}

