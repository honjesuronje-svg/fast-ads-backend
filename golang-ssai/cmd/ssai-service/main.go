package main

import (
	"context"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/fast-ads-backend/golang-ssai/internal/config"
	"github.com/fast-ads-backend/golang-ssai/internal/handler"
	"github.com/gin-gonic/gin"
)

func main() {
	// Load configuration
	configPath := os.Getenv("CONFIG_PATH")
	if configPath == "" {
		configPath = "configs/config.yaml"
	}

	cfg, err := config.LoadConfig(configPath)
	if err != nil {
		log.Fatalf("Failed to load config: %v", err)
	}

	// Set Gin mode
	if os.Getenv("GIN_MODE") == "" {
		gin.SetMode(gin.ReleaseMode)
	}

	// Initialize router
	router := gin.New()
	router.Use(gin.Logger())
	router.Use(gin.Recovery())

	// Initialize handlers
	manifestHandler := handler.NewManifestHandler(cfg)
	trackingHandler := handler.NewTrackingHandler(cfg)
	healthHandler := handler.NewHealthHandler()

	// Routes
	api := router.Group("/")
	{
		// Manifest endpoint - handle both with and without .m3u8 extension in handler
		api.GET("/fast/:tenant/:channel", manifestHandler.GetManifest)
		
		// Tracking endpoints
		api.POST("/tracking/impression", trackingHandler.TrackImpression)
		api.POST("/tracking/quartile", trackingHandler.TrackQuartile)
		api.POST("/tracking/complete", trackingHandler.TrackComplete)
		
		// Health check (must be before /fast/ to avoid route conflict)
		api.GET("/health", healthHandler.Health)
		api.GET("/metrics", healthHandler.Metrics)
	}

	// Create HTTP server
	srv := &http.Server{
		Addr:         fmt.Sprintf("%s:%d", cfg.Server.Host, cfg.Server.Port),
		Handler:      router,
		ReadTimeout:  cfg.Server.ReadTimeout,
		WriteTimeout: cfg.Server.WriteTimeout,
	}

	// Start server in goroutine
	go func() {
		log.Printf("Starting SSAI service on %s:%d", cfg.Server.Host, cfg.Server.Port)
		if err := srv.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.Fatalf("Failed to start server: %v", err)
		}
	}()

	// Wait for interrupt signal
	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	log.Println("Shutting down server...")

	// Graceful shutdown
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	if err := srv.Shutdown(ctx); err != nil {
		log.Fatalf("Server forced to shutdown: %v", err)
	}

	log.Println("Server exited")
}

