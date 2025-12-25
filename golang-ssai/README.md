# Golang SSAI Service

High-performance Server-Side Ad Insertion service for FAST channels.

## Architecture

- **Stateless**: No session storage, horizontally scalable
- **Fast**: Sub-50ms response times (cached)
- **Reliable**: Error handling, fallback to original manifest
- **Cache-aware**: Redis caching for manifests and ad decisions

## Folder Structure

```
golang-ssai/
├── cmd/
│   └── ssai-service/
│       └── main.go              # Application entry point
├── internal/
│   ├── handler/                 # HTTP handlers
│   │   ├── manifest.go          # HLS manifest handler
│   │   ├── tracking.go          # Tracking events handler
│   │   └── health.go            # Health check
│   ├── parser/                  # HLS parsing logic
│   │   ├── m3u8.go              # M3U8 parser
│   │   └── ad_break.go          # Ad break detection
│   ├── cache/                   # Caching layer
│   │   └── redis.go             # Redis client wrapper
│   ├── client/                  # External API clients
│   │   └── laravel.go           # Laravel API client
│   ├── config/                  # Configuration
│   │   └── config.go            # Config struct and loader
│   └── models/                  # Data models
│       ├── manifest.go          # Manifest models
│       └── ad.go                # Ad models
├── pkg/
│   ├── hls/                     # HLS utilities
│   │   └── manifest.go          # HLS manifest manipulation
│   └── scte35/                  # SCTE-35 parsing
│       └── parser.go            # SCTE-35 cue parser
├── configs/
│   └── config.yaml.example      # Example configuration
├── go.mod
├── go.sum
└── README.md
```

## Configuration

See `configs/config.yaml.example` for configuration options.

Key settings:
- Laravel API URL
- Redis connection
- Cache TTLs
- Rate limiting
- Logging

## Running

```bash
# Development
go run cmd/ssai-service/main.go

# Production
go build -o ssai-service cmd/ssai-service/main.go
./ssai-service
```

## Endpoints

- `GET /fast/{tenant}/{channel}.m3u8` - Get stitched manifest
- `GET /fast/{tenant}/{channel}/{segment}.ts` - Proxy segment requests
- `POST /tracking/impression` - Track ad impressions
- `POST /tracking/quartile` - Track ad quartiles
- `GET /health` - Health check
- `GET /metrics` - Prometheus metrics

## Dependencies

- `github.com/gin-gonic/gin` - HTTP router
- `github.com/redis/go-redis/v9` - Redis client
- `github.com/grafov/m3u8` - M3U8 parser (or custom)
- Custom SCTE-35 parser

