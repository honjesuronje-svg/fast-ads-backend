# Phase 2: SSAI FAST - Implementation Guide

## Overview
Phase 2 focuses on building the Golang SSAI service that handles high-performance HLS manifest manipulation and ad stitching.

## Current Status

### ✅ Completed
- Project structure created
- Core models defined
- HTTP handlers skeleton
- Configuration system
- Redis cache client
- Laravel API client
- HLS manifest parser (basic)
- Ad stitching logic (basic)

### 🔄 In Progress
- Complete HLS parser implementation
- SCTE-35 detection
- Full ad stitching with proper segment handling
- Error recovery mechanisms

### 📋 TODO
- Complete M3U8 parser with all tags
- Implement SCTE-35 full parsing
- Add manifest validation
- Performance optimization
- Comprehensive error handling
- Unit tests
- Integration tests

## Project Structure

```
golang-ssai/
├── cmd/ssai-service/
│   └── main.go              # Entry point
├── internal/
│   ├── handler/             # HTTP handlers
│   │   ├── manifest.go      # HLS manifest handler
│   │   ├── tracking.go      # Tracking events
│   │   └── health.go        # Health checks
│   ├── parser/              # Parsing logic
│   │   └── m3u8.go          # M3U8 parser
│   ├── cache/               # Caching
│   │   └── redis.go         # Redis client
│   ├── client/              # External clients
│   │   └── laravel.go       # Laravel API client
│   ├── config/              # Configuration
│   │   └── config.go        # Config loader
│   └── models/              # Data models
│       ├── manifest.go
│       └── ad.go
├── pkg/
│   ├── hls/                 # HLS utilities
│   │   └── manifest.go      # Manifest manipulation
│   └── scte35/              # SCTE-35 parsing
│       └── parser.go
└── configs/
    └── config.yaml.example
```

## Key Components

### 1. HLS Manifest Parser (`pkg/hls/manifest.go`)
- Parses M3U8 format
- Handles all standard HLS tags
- Supports ad segment insertion
- Renders back to M3U8

### 2. Manifest Handler (`internal/handler/manifest.go`)
- Fetches original manifest from CDN
- Detects ad breaks
- Calls Laravel for ad decisions
- Stitches ads into manifest
- Caches results

### 3. Laravel Client (`internal/client/laravel.go`)
- Calls Laravel `/ads/decision` endpoint
- Handles authentication
- Error handling and retries

### 4. Redis Cache (`internal/cache/redis.go`)
- Caches manifests (10s TTL)
- Caches ad decisions (60s TTL)
- Reduces load on Laravel API

## Implementation Steps

### Step 1: Install Go Dependencies
```bash
cd golang-ssai
go mod download
go mod tidy
```

### Step 2: Configure
```bash
cp configs/config.yaml.example configs/config.yaml
# Edit config.yaml with your settings
```

### Step 3: Build
```bash
make build
# or
go build -o bin/ssai-service ./cmd/ssai-service
```

### Step 4: Run
```bash
./bin/ssai-service
# or
make run
```

### Step 5: Test
```bash
# Test manifest endpoint
curl http://localhost:8080/fast/ott_a/news.m3u8

# Test health
curl http://localhost:8080/health
```

## Configuration

Edit `configs/config.yaml`:

```yaml
server:
  host: "0.0.0.0"
  port: 8080

laravel:
  base_url: "http://127.0.0.1:8000/api/v1"
  timeout: 5s

redis:
  host: "127.0.0.1:6379"
  password: ""
  db: 0

cache:
  manifest_ttl: 10s
  ad_decision_ttl: 60s

origins:
  default: "https://cdn.example.com"
  ott_a: "https://cdn-ott-a.example.com"
```

## Integration with Laravel

The Golang service calls Laravel API for ad decisions:

```
Golang → POST /api/v1/ads/decision
Headers: X-API-Key: {api_key}
Body: {
  "tenant_id": 1,
  "channel": "news",
  "ad_break_id": "break_123",
  "position": "mid-roll",
  "duration_seconds": 120,
  "geo": "US",
  "device": "android_tv"
}
```

## Next Steps

1. **Complete HLS Parser**
   - Support all HLS tags
   - Handle encryption keys
   - Support variant playlists

2. **SCTE-35 Implementation**
   - Full binary parsing
   - Cue detection in manifests
   - Integration with ad breaks

3. **Error Handling**
   - Fallback to original manifest
   - Retry logic for Laravel calls
   - Graceful degradation

4. **Performance**
   - Connection pooling
   - Async ad fetching
   - Better caching strategy

5. **Testing**
   - Unit tests for parser
   - Integration tests
   - Load testing

## Notes

- Go 1.21+ required
- Redis required for caching
- Laravel API must be running
- Origin CDN must be accessible

## Troubleshooting

### Connection Issues
- Check Laravel API is running
- Verify Redis is accessible
- Check network connectivity

### Manifest Issues
- Verify origin CDN URL is correct
- Check manifest format is valid HLS
- Review parser logs

### Ad Decision Issues
- Verify API key is correct
- Check Laravel logs
- Ensure tenant/channel exist

