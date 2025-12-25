package handler

import (
	"net/http"
	"time"

	"github.com/fast-ads-backend/golang-ssai/internal/client"
	"github.com/fast-ads-backend/golang-ssai/internal/config"
	"github.com/fast-ads-backend/golang-ssai/internal/models"
	"github.com/gin-gonic/gin"
)

type TrackingHandler struct {
	config        *config.Config
	laravelClient *client.LaravelClient
}

func NewTrackingHandler(cfg *config.Config) *TrackingHandler {
	laravelClient := client.NewLaravelClient(cfg)
	return &TrackingHandler{
		config:        cfg,
		laravelClient: laravelClient,
	}
}

// TrackImpression handles POST /tracking/impression
func (h *TrackingHandler) TrackImpression(c *gin.Context) {
	var event models.TrackingEvent
	if err := c.ShouldBindJSON(&event); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	event.EventType = "impression"
	event.Timestamp = time.Now().UTC().Format(time.RFC3339)
	event.IPAddress = c.ClientIP()
	event.UserAgent = c.GetHeader("User-Agent")

	// Send to Laravel (async in production)
	if err := h.laravelClient.SendTrackingEvent(c.Request.Context(), event); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to track event"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"success": true})
}

// TrackQuartile handles POST /tracking/quartile
func (h *TrackingHandler) TrackQuartile(c *gin.Context) {
	var event models.TrackingEvent
	if err := c.ShouldBindJSON(&event); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	event.Timestamp = time.Now().UTC().Format(time.RFC3339)
	event.IPAddress = c.ClientIP()
	event.UserAgent = c.GetHeader("User-Agent")

	if err := h.laravelClient.SendTrackingEvent(c.Request.Context(), event); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to track event"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"success": true})
}

// TrackComplete handles POST /tracking/complete
func (h *TrackingHandler) TrackComplete(c *gin.Context) {
	var event models.TrackingEvent
	if err := c.ShouldBindJSON(&event); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	event.EventType = "complete"
	event.Timestamp = time.Now().UTC().Format(time.RFC3339)
	event.IPAddress = c.ClientIP()
	event.UserAgent = c.GetHeader("User-Agent")

	if err := h.laravelClient.SendTrackingEvent(c.Request.Context(), event); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "Failed to track event"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"success": true})
}

