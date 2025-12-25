package models

// AdDecisionRequest is sent to Laravel
type AdDecisionRequest struct {
	TenantID      int    `json:"tenant_id"`
	Channel       string `json:"channel"`
	AdBreakID     string `json:"ad_break_id"`
	Position      string `json:"position"`
	DurationSeconds int  `json:"duration_seconds"`
	Geo           string `json:"geo,omitempty"`
	Device        string `json:"device,omitempty"`
	Timestamp     string `json:"timestamp"`
}

// AdDecisionResponse is received from Laravel
type AdDecisionResponse struct {
	Success bool `json:"success"`
	Data    struct {
		Ads              []Ad `json:"ads"`
		TotalDurationSeconds int `json:"total_duration_seconds"`
		PodID            string `json:"pod_id"`
	} `json:"data"`
}

// Ad represents a single ad
type Ad struct {
	AdID          int    `json:"ad_id"`
	VASTURL       string `json:"vast_url"`
	DurationSeconds int  `json:"duration_seconds"`
	AdType        string `json:"ad_type"`
	ClickThroughURL string `json:"click_through_url,omitempty"`
	TrackingURLs  struct {
		Impression    string `json:"impression,omitempty"`
		Start         string `json:"start,omitempty"`
		FirstQuartile string `json:"first_quartile,omitempty"`
		Midpoint      string `json:"midpoint,omitempty"`
		ThirdQuartile string `json:"third_quartile,omitempty"`
		Complete      string `json:"complete,omitempty"`
	} `json:"tracking_urls"`
}

// TrackingEvent represents an ad tracking event
type TrackingEvent struct {
	TenantID  int    `json:"tenant_id"`
	ChannelID int    `json:"channel_id,omitempty"`
	AdID      int    `json:"ad_id"`
	EventType string `json:"event_type"` // impression, start, first_quartile, etc.
	SessionID string `json:"session_id,omitempty"`
	DeviceType string `json:"device_type,omitempty"`
	GeoCountry string `json:"geo_country,omitempty"`
	IPAddress string `json:"ip_address,omitempty"`
	UserAgent string `json:"user_agent,omitempty"`
	Timestamp string `json:"timestamp"`
	Metadata  map[string]interface{} `json:"metadata,omitempty"`
}

// ChannelConfig represents channel ad break configuration
type ChannelConfig struct {
	ChannelID int       `json:"channel_id"`
	AdRules   []AdRule  `json:"ad_rules"`
}

// AdRule represents a static ad break rule
type AdRule struct {
	Position string  `json:"position"` // pre-roll, mid-roll, post-roll
	Offset   float64 `json:"offset"`   // seconds from start (for mid-roll) or end (for post-roll)
	Duration int     `json:"duration"` // expected duration in seconds
	Interval float64 `json:"interval,omitempty"` // repeat interval in seconds (for multiple mid-rolls)
}

// ChannelInfo represents channel information from Laravel
type ChannelInfo struct {
	ID                      int    `json:"id"`
	TenantID                int    `json:"tenant_id"`
	Name                    string `json:"name"`
	Slug                    string `json:"slug"`
	HLSManifestURL          string `json:"hls_manifest_url"`
	AdBreakStrategy         string `json:"ad_break_strategy"`
	AdBreakIntervalSeconds  int    `json:"ad_break_interval_seconds"`
	EnablePreRoll           bool   `json:"enable_pre_roll"`
	Status                  string `json:"status"`
}

