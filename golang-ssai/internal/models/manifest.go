package models

// Manifest represents an HLS manifest
type Manifest struct {
	Version     int
	PlaylistType string
	TargetDuration int
	MediaSequence int64
	Segments    []Segment
	AdBreaks    []AdBreak
}

// Segment represents an HLS segment
type Segment struct {
	URI         string
	Duration    float64
	Title       string
	Discontinuity bool
	ByteRange   string
	Key         *Key
}

// Key represents encryption key info
type Key struct {
	Method string
	URI    string
	IV     string
}

// AdBreak represents an ad break position
type AdBreak struct {
	ID          string
	Position    string // pre-roll, mid-roll, post-roll
	Offset      float64 // seconds from start
	Duration    int    // expected duration in seconds
	Type        string // scte35, static
}

