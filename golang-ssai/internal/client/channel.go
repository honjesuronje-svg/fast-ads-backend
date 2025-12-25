package client

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"

	"github.com/fast-ads-backend/golang-ssai/internal/models"
)

// GetChannelBySlug gets channel information by tenant slug and channel slug
func (c *LaravelClient) GetChannelBySlug(ctx context.Context, tenantSlug, channelSlug string) (*models.ChannelInfo, error) {
	// Call Laravel API to get channel info
	url := fmt.Sprintf("%s/api/v1/channels/%s/%s", c.baseURL, tenantSlug, channelSlug)

	httpReq, err := http.NewRequestWithContext(ctx, "GET", url, nil)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

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

	var response struct {
		Success bool              `json:"success"`
		Data     models.ChannelInfo `json:"data"`
	}

	if err := json.NewDecoder(resp.Body).Decode(&response); err != nil {
		return nil, fmt.Errorf("failed to decode response: %w", err)
	}

	if !response.Success {
		return nil, fmt.Errorf("API returned success=false")
	}

	return &response.Data, nil
}

