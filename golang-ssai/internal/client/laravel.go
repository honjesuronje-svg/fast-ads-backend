package client

import (
	"bytes"
	"context"
	"crypto/tls"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"time"

	"github.com/fast-ads-backend/golang-ssai/internal/config"
	"github.com/fast-ads-backend/golang-ssai/internal/models"
)

type LaravelClient struct {
	baseURL string
	timeout time.Duration
	client  *http.Client
	apiKey  string
}

func NewLaravelClient(cfg *config.Config) *LaravelClient {
	apiKey := "test_api_key_123" // Default, should come from config or tenant lookup
	if cfg.Laravel.APIKey != "" {
		apiKey = cfg.Laravel.APIKey
	}

	baseURL := cfg.Laravel.BaseURL
	// Ensure base URL doesn't have trailing slash
	if len(baseURL) > 0 && baseURL[len(baseURL)-1] == '/' {
		baseURL = baseURL[:len(baseURL)-1]
	}

	// Create HTTP client with custom transport for SSL verification
	tr := &http.Transport{
		TLSClientConfig: &tls.Config{
			InsecureSkipVerify: true, // Skip SSL verification for now (use proper certs in production)
		},
	}

	return &LaravelClient{
		baseURL: baseURL,
		timeout: cfg.Laravel.Timeout,
		apiKey:  apiKey,
		client: &http.Client{
			Timeout:   cfg.Laravel.Timeout,
			Transport: tr,
		},
	}
}

// GetAdDecision calls Laravel /ads/decision endpoint
func (c *LaravelClient) GetAdDecision(ctx context.Context, req models.AdDecisionRequest) (*models.AdDecisionResponse, error) {
	url := fmt.Sprintf("%s/api/v1/ads/decision", c.baseURL)

	reqBody, err := json.Marshal(req)
	if err != nil {
		return nil, fmt.Errorf("failed to marshal request: %w", err)
	}

	httpReq, err := http.NewRequestWithContext(ctx, "POST", url, bytes.NewBuffer(reqBody))
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	httpReq.Header.Set("Content-Type", "application/json")
	httpReq.Header.Set("X-API-Key", c.apiKey)

	resp, err := c.client.Do(httpReq)
	if err != nil {
		return nil, fmt.Errorf("failed to send request: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		body, _ := io.ReadAll(resp.Body)
		return nil, fmt.Errorf("unexpected status %d: %s", resp.StatusCode, string(body))
	}

	var adResp models.AdDecisionResponse
	if err := json.NewDecoder(resp.Body).Decode(&adResp); err != nil {
		return nil, fmt.Errorf("failed to decode response: %w", err)
	}

	return &adResp, nil
}

// GetChannelConfig gets channel ad break configuration from Laravel
func (c *LaravelClient) GetChannelConfig(ctx context.Context, tenantID int, channelSlug string) (*models.ChannelConfig, error) {
	url := fmt.Sprintf("%s/channels/%s/config", c.baseURL, channelSlug)

	httpReq, err := http.NewRequestWithContext(ctx, "GET", url, nil)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	httpReq.Header.Set("X-API-Key", c.apiKey)
	httpReq.Header.Set("X-Tenant-ID", fmt.Sprintf("%d", tenantID))

	resp, err := c.client.Do(httpReq)
	if err != nil {
		return nil, fmt.Errorf("failed to send request: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		// Return default config if not found
		return &models.ChannelConfig{
			AdRules: []models.AdRule{},
		}, nil
	}

	var config models.ChannelConfig
	if err := json.NewDecoder(resp.Body).Decode(&config); err != nil {
		return nil, fmt.Errorf("failed to decode response: %w", err)
	}

	return &config, nil
}

// SendTrackingEvent sends tracking event to Laravel
func (c *LaravelClient) SendTrackingEvent(ctx context.Context, event models.TrackingEvent) error {
	url := fmt.Sprintf("%s/api/v1/tracking/events", c.baseURL)

	reqBody := map[string]interface{}{
		"events": []models.TrackingEvent{event},
	}

	jsonData, err := json.Marshal(reqBody)
	if err != nil {
		return fmt.Errorf("failed to marshal event: %w", err)
	}

	httpReq, err := http.NewRequestWithContext(ctx, "POST", url, bytes.NewBuffer(jsonData))
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}

	httpReq.Header.Set("Content-Type", "application/json")
	httpReq.Header.Set("X-API-Key", c.apiKey)

	resp, err := c.client.Do(httpReq)
	if err != nil {
		return fmt.Errorf("failed to send request: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		body, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("unexpected status %d: %s", resp.StatusCode, string(body))
	}

	return nil
}
