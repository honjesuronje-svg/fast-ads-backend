package handler

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

type HealthHandler struct{}

func NewHealthHandler() *HealthHandler {
	return &HealthHandler{}
}

// Health handles GET /health
func (h *HealthHandler) Health(c *gin.Context) {
	c.JSON(http.StatusOK, gin.H{
		"status": "healthy",
		"service": "ssai-service",
	})
}

// Metrics handles GET /metrics (Prometheus format)
func (h *HealthHandler) Metrics(c *gin.Context) {
	// TODO: Implement Prometheus metrics
	c.String(http.StatusOK, "# Metrics endpoint\n")
}

