package hls

import (
	"fmt"
	"strings"
	"time"
)

// Manifest represents a parsed HLS manifest
type Manifest struct {
	Version         int
	PlaylistType    string
	TargetDuration  float64
	MediaSequence   int64
	Segments        []Segment
	Discontinuity   bool
	EndList         bool
}

// Segment represents an HLS segment
type Segment struct {
	URI             string
	Duration        float64
	Title           string
	Discontinuity   bool
	ByteRange       *ByteRange
	Key             *Key
	ProgramDateTime *time.Time
}

// ByteRange represents EXT-X-BYTERANGE
type ByteRange struct {
	Length int64
	Offset int64
}

// Key represents EXT-X-KEY
type Key struct {
	Method string
	URI    string
	IV     string
	KeyFormat      string
	KeyFormatVersions string
}

// ParseManifest parses an M3U8 manifest string
func ParseManifest(content string) (*Manifest, error) {
	lines := strings.Split(content, "\n")
	m := &Manifest{
		Segments: []Segment{},
	}

	var currentSegment *Segment
	var currentKey *Key

	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line == "" || strings.HasPrefix(line, "#EXTM3U") {
			continue
		}

		// Parse tags
		if strings.HasPrefix(line, "#EXT-X-VERSION:") {
			fmt.Sscanf(line, "#EXT-X-VERSION:%d", &m.Version)
		} else if strings.HasPrefix(line, "#EXT-X-PLAYLIST-TYPE:") {
			m.PlaylistType = strings.TrimPrefix(line, "#EXT-X-PLAYLIST-TYPE:")
		} else if strings.HasPrefix(line, "#EXT-X-TARGETDURATION:") {
			fmt.Sscanf(line, "#EXT-X-TARGETDURATION:%f", &m.TargetDuration)
		} else if strings.HasPrefix(line, "#EXT-X-MEDIA-SEQUENCE:") {
			fmt.Sscanf(line, "#EXT-X-MEDIA-SEQUENCE:%d", &m.MediaSequence)
		} else if strings.HasPrefix(line, "#EXT-X-DISCONTINUITY") {
			m.Discontinuity = true
			if currentSegment != nil {
				currentSegment.Discontinuity = true
			}
		} else if strings.HasPrefix(line, "#EXT-X-ENDLIST") {
			m.EndList = true
		} else if strings.HasPrefix(line, "#EXT-X-KEY:") {
			currentKey = parseKey(line)
		} else if strings.HasPrefix(line, "#EXT-X-BYTERANGE:") {
			if currentSegment != nil {
				currentSegment.ByteRange = parseByteRange(line)
			}
		} else if strings.HasPrefix(line, "#EXT-X-PROGRAM-DATE-TIME:") {
			// Parse PROGRAM-DATE-TIME
			// Format: 2025-12-25T02:05:34.242Z or 2025-12-25T02:05:34.242000000Z
			dateTimeStr := strings.TrimPrefix(line, "#EXT-X-PROGRAM-DATE-TIME:")
			// Try RFC3339 format first (with milliseconds)
			programDateTime, err := time.Parse("2006-01-02T15:04:05.000Z", dateTimeStr)
			if err != nil {
				// Try RFC3339Nano
				programDateTime, err = time.Parse(time.RFC3339Nano, dateTimeStr)
			}
			if err == nil {
				// Store ProgramDateTime for next segment
				if currentSegment != nil {
					currentSegment.ProgramDateTime = &programDateTime
				} else {
					// Create a temporary segment to store ProgramDateTime
					currentSegment = &Segment{
						ProgramDateTime: &programDateTime,
					}
				}
			}
		} else if strings.HasPrefix(line, "#EXTINF:") {
			var duration float64
			var title string
			fmt.Sscanf(line, "#EXTINF:%f,%s", &duration, &title)
			
			// Preserve ProgramDateTime if already set
			var programDateTime *time.Time
			if currentSegment != nil && currentSegment.ProgramDateTime != nil {
				programDateTime = currentSegment.ProgramDateTime
			}
			
			currentSegment = &Segment{
				Duration: duration,
				Title:    title,
				Key:      currentKey,
				ProgramDateTime: programDateTime,
			}
		} else if !strings.HasPrefix(line, "#") && currentSegment != nil {
			// This is a URI
			currentSegment.URI = line
			m.Segments = append(m.Segments, *currentSegment)
			currentSegment = nil
		}
	}

	return m, nil
}

// InsertAdSegments inserts ad segments into manifest at specified position
// insertIndex: 0 = before first segment (pre-roll), >0 = after segment at that index
func InsertAdSegments(m *Manifest, adSegments []AdSegment, insertIndex int) error {
	if insertIndex < 0 {
		return fmt.Errorf("invalid insert position: %d", insertIndex)
	}
	
	// Special case: pre-roll (insertIndex = 0) - insert before first segment
	if insertIndex == 0 {
		// Create new segments slice
		newSegments := make([]Segment, 0, len(m.Segments)+len(adSegments))
		
		// Calculate ProgramDateTime for ad segments
		// For ExoPlayer compatibility: use current time for ad, not past time
		// ExoPlayer may skip ads if ProgramDateTime is too far in the past
		now := time.Now().UTC()
		var adStartTime time.Time
		
		// Calculate total ad duration
		var totalAdDuration time.Duration
		for _, adSeg := range adSegments {
			totalAdDuration += time.Duration(adSeg.Duration) * time.Second
		}
		
		// Use current time for ad start (ExoPlayer compatible)
		adStartTime = now
		
		// Adjust origin ProgramDateTime to be after ad
		if len(m.Segments) > 0 {
			originStartTime := adStartTime.Add(totalAdDuration)
			m.Segments[0].ProgramDateTime = &originStartTime
			fmt.Printf("DEBUG: Ad ProgramDateTime: %v, Origin ProgramDateTime: %v (ad duration: %v)\n", 
				adStartTime, originStartTime, totalAdDuration)
		}
		
		// DO NOT adjust media sequence for LIVE streams
		// LIVE streams must maintain their original sequence number
		// Ads are inserted with DISCONTINUITY markers which allow sequence gaps
		fmt.Printf("DEBUG: Maintaining original media sequence for LIVE stream: %d\n", m.MediaSequence)
		
		// Add ad segments first (pre-roll) with discontinuity marker
		for i, adSeg := range adSegments {
			// Calculate ProgramDateTime for this ad segment
			segmentStartTime := adStartTime
			if i > 0 {
				// Add duration of previous ad segments
				for j := 0; j < i; j++ {
					segmentStartTime = segmentStartTime.Add(time.Duration(adSegments[j].Duration) * time.Second)
				}
			}
			
			// First ad segment gets discontinuity marker
			newSegments = append(newSegments, Segment{
				URI:      adSeg.URI,
				Duration: adSeg.Duration,
				Title:    adSeg.Title,
				Discontinuity: i == 0, // Only first ad segment has discontinuity
				ProgramDateTime: &segmentStartTime, // Synchronized ProgramDateTime
			})
		}
		
		// Add discontinuity marker after ads (before origin content)
		if len(adSegments) > 0 && len(m.Segments) > 0 {
			// Set discontinuity on first origin segment
			if len(m.Segments) > 0 {
				firstOrigin := m.Segments[0]
				firstOrigin.Discontinuity = true
				newSegments = append(newSegments, firstOrigin)
				// Add remaining origin segments
				newSegments = append(newSegments, m.Segments[1:]...)
			}
		} else {
			// No ads or no origin segments, just add all origin segments
			newSegments = append(newSegments, m.Segments...)
		}
		
		m.Segments = newSegments
		return nil
	}
	
	// Mid-roll: insert after segment at insertIndex
	if insertIndex >= len(m.Segments) {
		return fmt.Errorf("invalid insert position: %d (manifest has %d segments)", insertIndex, len(m.Segments))
	}

	// Create new segments slice
	newSegments := make([]Segment, 0, len(m.Segments)+len(adSegments))
	
	// Add segments before insertion point
	newSegments = append(newSegments, m.Segments[:insertIndex+1]...)
	
	// Add ad segments with discontinuity on first ad only
	for i, adSeg := range adSegments {
		newSegments = append(newSegments, Segment{
			URI:           adSeg.URI,
			Duration:      adSeg.Duration,
			Title:         adSeg.Title,
			Discontinuity: i == 0, // Only first ad segment has discontinuity
		})
	}
	
	// Add discontinuity marker on FIRST segment after ads
	if insertIndex+1 < len(m.Segments) {
		remainingSegments := m.Segments[insertIndex+1:]
		if len(remainingSegments) > 0 {
			remainingSegments[0].Discontinuity = true
			newSegments = append(newSegments, remainingSegments...)
		}
	}
	
	m.Segments = newSegments
	return nil
}

// AdSegment represents an ad segment to be inserted
type AdSegment struct {
	URI      string
	Duration float64
	Title    string
	IsVAST   bool // True if URI is a VAST URL that needs processing
}

// RenderManifest converts manifest back to M3U8 string
func RenderManifest(m *Manifest) string {
	var sb strings.Builder
	
	sb.WriteString("#EXTM3U\n")
	
	if m.Version > 0 {
		sb.WriteString(fmt.Sprintf("#EXT-X-VERSION:%d\n", m.Version))
	}
	
	// Add EXT-X-INDEPENDENT-SEGMENTS for ExoPlayer compatibility
	// This tells the player that segments can be decoded independently
	sb.WriteString("#EXT-X-INDEPENDENT-SEGMENTS\n")
	
	if m.PlaylistType != "" {
		sb.WriteString(fmt.Sprintf("#EXT-X-PLAYLIST-TYPE:%s\n", m.PlaylistType))
	}
	
	if m.TargetDuration > 0 {
		sb.WriteString(fmt.Sprintf("#EXT-X-TARGETDURATION:%.0f\n", m.TargetDuration))
	}
	
	if m.MediaSequence > 0 {
		sb.WriteString(fmt.Sprintf("#EXT-X-MEDIA-SEQUENCE:%d\n", m.MediaSequence))
	}
	
	var currentKey *Key
	for _, seg := range m.Segments {
		if seg.Discontinuity {
			sb.WriteString("#EXT-X-DISCONTINUITY\n")
		}
		
		if seg.Key != nil && seg.Key != currentKey {
			sb.WriteString(fmt.Sprintf("#EXT-X-KEY:METHOD=%s", seg.Key.Method))
			if seg.Key.URI != "" {
				sb.WriteString(fmt.Sprintf(",URI=\"%s\"", seg.Key.URI))
			}
			if seg.Key.IV != "" {
				sb.WriteString(fmt.Sprintf(",IV=%s", seg.Key.IV))
			}
			sb.WriteString("\n")
			currentKey = seg.Key
		}
		
		if seg.ByteRange != nil {
			sb.WriteString(fmt.Sprintf("#EXT-X-BYTERANGE:%d@%d\n", seg.ByteRange.Length, seg.ByteRange.Offset))
		}
		
		// Add PROGRAM-DATE-TIME if available (for Flussonic compatibility)
		// Flussonic uses format: 2025-12-25T02:05:34.242Z (RFC3339 with milliseconds, not nanoseconds)
		if seg.ProgramDateTime != nil {
			// Format: 2025-12-25T02:05:34.242Z (same as Flussonic)
			formatted := seg.ProgramDateTime.Format("2006-01-02T15:04:05.000Z")
			sb.WriteString(fmt.Sprintf("#EXT-X-PROGRAM-DATE-TIME:%s\n", formatted))
		}
		
		if seg.Duration > 0 {
			// HLS standard: EXTINF format is "#EXTINF:duration," (title is optional but can cause parsing issues)
			// Remove title to ensure compatibility with all HLS players including VLC
			sb.WriteString(fmt.Sprintf("#EXTINF:%.3f,\n", seg.Duration))
			sb.WriteString(seg.URI + "\n")
		}
	}
	
	if m.EndList {
		sb.WriteString("#EXT-X-ENDLIST\n")
	}
	
	return sb.String()
}

func parseKey(line string) *Key {
	key := &Key{Method: "NONE"}
	
	// Simple parsing - in production use proper parsing
	if strings.Contains(line, "METHOD=AES-128") {
		key.Method = "AES-128"
	}
	
	return key
}

func parseByteRange(line string) *ByteRange {
	var length, offset int64
	fmt.Sscanf(line, "#EXT-X-BYTERANGE:%d@%d", &length, &offset)
	return &ByteRange{Length: length, Offset: offset}
}

