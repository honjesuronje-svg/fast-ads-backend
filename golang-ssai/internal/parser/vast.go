package parser

import (
	"encoding/xml"
	"fmt"
	"io"
	"net/http"
	"strings"
)

// VAST represents the root VAST element
type VAST struct {
	XMLName xml.Name `xml:"VAST"`
	Version string   `xml:"version,attr"`
	Ad      Ad       `xml:"Ad"`
}

// Ad represents an ad in VAST
type Ad struct {
	ID     string  `xml:"id,attr"`
	InLine *InLine `xml:"InLine"`
	Wrapper *Wrapper `xml:"Wrapper"`
}

// InLine represents inline ad content
type InLine struct {
	AdSystem    string     `xml:"AdSystem"`
	AdTitle     string     `xml:"AdTitle"`
	Creatives   Creatives  `xml:"Creatives"`
}

// Wrapper represents a VAST wrapper (redirect to another VAST)
type Wrapper struct {
	AdSystem     string `xml:"AdSystem"`
	VASTAdTagURI string `xml:"VASTAdTagURI"`
}

// Creatives contains creative elements
type Creatives struct {
	Creative []Creative `xml:"Creative"`
}

// Creative represents a creative element
type Creative struct {
	ID     string `xml:"id,attr"`
	Linear *Linear `xml:"Linear"`
}

// Linear represents linear ad
type Linear struct {
	Duration      string        `xml:"Duration"`
	MediaFiles    MediaFiles    `xml:"MediaFiles"`
	TrackingEvents TrackingEvents `xml:"TrackingEvents"`
	VideoClicks   VideoClicks   `xml:"VideoClicks"`
}

// MediaFiles contains media file elements
type MediaFiles struct {
	MediaFile []MediaFile `xml:"MediaFile"`
}

// MediaFile represents a media file
type MediaFile struct {
	ID       string `xml:"id,attr"`
	Type     string `xml:"type,attr"`
	Delivery string `xml:"delivery,attr"`
	Bitrate  string `xml:"bitrate,attr"`
	Width    string `xml:"width,attr"`
	Height   string `xml:"height,attr"`
	URL      string `xml:",chardata"` // CDATA content
}

// TrackingEvents contains tracking URLs
type TrackingEvents struct {
	Tracking []Tracking `xml:"Tracking"`
}

// Tracking represents a tracking event
type Tracking struct {
	Event string `xml:"event,attr"`
	URL   string `xml:",chardata"`
}

// VideoClicks contains click URLs
type VideoClicks struct {
	ClickThrough  string `xml:"ClickThrough"`
	ClickTracking string `xml:"ClickTracking"`
}

// VASTParser handles VAST XML parsing
type VASTParser struct{}

// NewVASTParser creates a new VAST parser
func NewVASTParser() *VASTParser {
	return &VASTParser{}
}

// FetchVAST fetches VAST XML from URL
func (p *VASTParser) FetchVAST(vastURL string) (string, error) {
	resp, err := http.Get(vastURL)
	if err != nil {
		return "", fmt.Errorf("failed to fetch VAST: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return "", fmt.Errorf("VAST fetch failed with status: %d", resp.StatusCode)
	}

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return "", fmt.Errorf("failed to read VAST response: %w", err)
	}

	return string(body), nil
}

// ParseVAST parses VAST XML string
func (p *VASTParser) ParseVAST(vastXML string) (*VAST, error) {
	var vast VAST
	err := xml.Unmarshal([]byte(vastXML), &vast)
	if err != nil {
		return nil, fmt.Errorf("failed to parse VAST XML: %w", err)
	}

	// Handle VAST wrapper (redirect)
	if vast.Ad.Wrapper != nil && vast.Ad.Wrapper.VASTAdTagURI != "" {
		// Fetch wrapped VAST
		wrappedVAST, err := p.FetchVAST(strings.TrimSpace(vast.Ad.Wrapper.VASTAdTagURI))
		if err != nil {
			return nil, fmt.Errorf("failed to fetch wrapped VAST: %w", err)
		}
		// Parse wrapped VAST recursively
		return p.ParseVAST(wrappedVAST)
	}

	return &vast, nil
}

// ExtractVideoURLs extracts video URLs from VAST
// Returns URLs from MediaFile elements
func (p *VASTParser) ExtractVideoURLs(vast *VAST) []string {
	var urls []string

	if vast.Ad.InLine == nil {
		return urls
	}

	for _, creative := range vast.Ad.InLine.Creatives.Creative {
		if creative.Linear == nil {
			continue
		}

		for _, mediaFile := range creative.Linear.MediaFiles.MediaFile {
			url := strings.TrimSpace(mediaFile.URL)
			if url != "" {
				urls = append(urls, url)
			}
		}
	}

	return urls
}

// ExtractHLSManifestURL extracts HLS manifest URL from VAST
// Looks for MediaFile with type="application/x-mpegURL"
func (p *VASTParser) ExtractHLSManifestURL(vast *VAST) (string, error) {
	if vast.Ad.InLine == nil {
		return "", fmt.Errorf("no inline ad content")
	}

	for _, creative := range vast.Ad.InLine.Creatives.Creative {
		if creative.Linear == nil {
			continue
		}

		for _, mediaFile := range creative.Linear.MediaFiles.MediaFile {
			// Check if it's HLS manifest
			if mediaFile.Type == "application/x-mpegURL" || 
			   mediaFile.Type == "application/vnd.apple.mpegurl" ||
			   strings.HasSuffix(strings.ToLower(mediaFile.URL), ".m3u8") {
				url := strings.TrimSpace(mediaFile.URL)
				if url != "" {
					return url, nil
				}
			}
		}
	}

	return "", fmt.Errorf("no HLS manifest URL found in VAST")
}

// ExtractMP4URL extracts MP4 video URL from VAST
// Returns first MP4 URL found
func (p *VASTParser) ExtractMP4URL(vast *VAST) (string, error) {
	if vast.Ad.InLine == nil {
		return "", fmt.Errorf("no inline ad content")
	}

	for _, creative := range vast.Ad.InLine.Creatives.Creative {
		if creative.Linear == nil {
			continue
		}

		for _, mediaFile := range creative.Linear.MediaFiles.MediaFile {
			// Check if it's MP4
			if mediaFile.Type == "video/mp4" || strings.HasSuffix(strings.ToLower(mediaFile.URL), ".mp4") {
				url := strings.TrimSpace(mediaFile.URL)
				if url != "" {
					return url, nil
				}
			}
		}
	}

	return "", fmt.Errorf("no MP4 URL found in VAST")
}

// ExtractTrackingURLs extracts tracking URLs from VAST
func (p *VASTParser) ExtractTrackingURLs(vast *VAST) map[string]string {
	trackingURLs := make(map[string]string)

	if vast.Ad.InLine == nil {
		return trackingURLs
	}

	for _, creative := range vast.Ad.InLine.Creatives.Creative {
		if creative.Linear == nil {
			continue
		}

		for _, tracking := range creative.Linear.TrackingEvents.Tracking {
			event := strings.ToLower(tracking.Event)
			url := strings.TrimSpace(tracking.URL)
			if url != "" {
				trackingURLs[event] = url
			}
		}
	}

	return trackingURLs
}

// ExtractClickThroughURL extracts click-through URL from VAST
func (p *VASTParser) ExtractClickThroughURL(vast *VAST) string {
	if vast.Ad.InLine == nil {
		return ""
	}

	for _, creative := range vast.Ad.InLine.Creatives.Creative {
		if creative.Linear == nil {
			continue
		}

		clickURL := strings.TrimSpace(creative.Linear.VideoClicks.ClickThrough)
		if clickURL != "" {
			return clickURL
		}
	}

	return ""
}

// ProcessVAST processes VAST URL and extracts all relevant information
func (p *VASTParser) ProcessVAST(vastURL string) (*VASTInfo, error) {
	// Fetch VAST XML
	vastXML, err := p.FetchVAST(vastURL)
	if err != nil {
		return nil, err
	}

	// Parse VAST
	vast, err := p.ParseVAST(vastXML)
	if err != nil {
		return nil, err
	}

	// Extract information
	info := &VASTInfo{
		HLSManifestURL: "",
		MP4URL:        "",
		VideoURLs:     p.ExtractVideoURLs(vast),
		TrackingURLs:  p.ExtractTrackingURLs(vast),
		ClickThroughURL: p.ExtractClickThroughURL(vast),
	}

	// Try to get HLS manifest URL
	if hlsURL, err := p.ExtractHLSManifestURL(vast); err == nil {
		info.HLSManifestURL = hlsURL
	}

	// Try to get MP4 URL
	if mp4URL, err := p.ExtractMP4URL(vast); err == nil {
		info.MP4URL = mp4URL
	}

	return info, nil
}

// VASTInfo contains extracted information from VAST
type VASTInfo struct {
	HLSManifestURL  string            // HLS manifest URL (.m3u8)
	MP4URL          string            // MP4 video URL
	VideoURLs       []string          // All video URLs found
	TrackingURLs    map[string]string // Event -> URL mapping
	ClickThroughURL string            // Click-through URL
}

