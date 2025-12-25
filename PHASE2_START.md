# Phase 2: SSAI FAST - Getting Started

## ✅ Phase 1 Complete
- Laravel API fully functional
- Database setup and migrations
- Sample data seeded
- All API endpoints working

## 🚀 Phase 2: Golang SSAI Service

### Prerequisites
1. **Go 1.21+** installed
   ```bash
   # Check if Go is installed
   go version
   
   # If not installed, install Go:
   # Ubuntu/Debian:
   sudo apt-get update
   sudo apt-get install golang-go
   
   # Or download from: https://go.dev/dl/
   ```

2. **Redis** running (for caching)
   ```bash
   # Check if Redis is running
   redis-cli ping
   
   # If not, install and start:
   sudo apt-get install redis-server
   sudo systemctl start redis
   ```

3. **Laravel API** running (from Phase 1)
   ```bash
   cd /home/lamkapro/fast-ads-backend/laravel-backend
   php artisan serve
   ```

### Quick Start

#### 1. Install Go Dependencies
```bash
cd /home/lamkapro/fast-ads-backend/golang-ssai

# Download dependencies
go mod download

# Verify
go mod verify
```

#### 2. Configure Service
```bash
# Copy example config
cp configs/config.yaml.example configs/config.yaml

# Edit config (update Laravel URL, Redis host, etc.)
nano configs/config.yaml
```

#### 3. Build Service
```bash
# Using Makefile
make build

# Or manually
go build -o bin/ssai-service ./cmd/ssai-service
```

#### 4. Run Service
```bash
# Run directly
./bin/ssai-service

# Or using Makefile
make run

# Or with custom config
CONFIG_PATH=/path/to/config.yaml ./bin/ssai-service
```

#### 5. Test Service
```bash
# Health check
curl http://localhost:8080/health

# Test manifest endpoint (requires origin CDN)
curl http://localhost:8080/fast/ott_a/news.m3u8
```

## Current Implementation Status

### ✅ Completed Files
- `cmd/ssai-service/main.go` - Application entry point
- `internal/handler/manifest.go` - Manifest handler (updated)
- `internal/handler/tracking.go` - Tracking handler
- `internal/handler/health.go` - Health check
- `internal/client/laravel.go` - Laravel API client
- `internal/cache/redis.go` - Redis cache
- `internal/config/config.go` - Configuration
- `internal/models/*.go` - Data models
- `pkg/hls/manifest.go` - HLS parser (NEW - complete implementation)
- `pkg/scte35/parser.go` - SCTE-35 parser (skeleton)
- `configs/config.yaml.example` - Example config

### 🔄 Needs Completion
1. **HLS Parser** - Basic implementation done, needs:
   - Full tag support (#EXT-X-PROGRAM-DATE-TIME, etc.)
   - Variant playlist support
   - Better error handling

2. **SCTE-35** - Skeleton done, needs:
   - Full binary parsing
   - Integration with manifest parser
   - Cue detection logic

3. **Ad Stitching** - Basic done, needs:
   - Proper segment duration calculation
   - Media sequence updates
   - Discontinuity handling improvements

4. **Error Handling** - Needs:
   - Fallback mechanisms
   - Retry logic
   - Better logging

## Testing Strategy

### Unit Tests
```bash
# Run all tests
go test ./...

# Run with coverage
go test -cover ./...

# Run specific package
go test ./internal/parser
```

### Integration Tests
1. Start Laravel API
2. Start Redis
3. Start Golang service
4. Test end-to-end flow

### Manual Testing
```bash
# 1. Start services
# Terminal 1: Laravel
cd laravel-backend && php artisan serve

# Terminal 2: Redis (if not running)
redis-server

# Terminal 3: Golang
cd golang-ssai && ./bin/ssai-service

# 2. Test endpoints
curl http://localhost:8080/health
curl http://localhost:8080/fast/ott_a/news.m3u8
```

## Configuration Example

```yaml
server:
  host: "0.0.0.0"
  port: 8080

laravel:
  base_url: "http://127.0.0.1:8000/api/v1"
  timeout: 5s
  retry_attempts: 3

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

## Next Actions

1. **Install Go** (if not installed)
2. **Install dependencies**: `go mod download`
3. **Configure**: Edit `configs/config.yaml`
4. **Build**: `make build`
5. **Test**: Run and test endpoints
6. **Complete implementations**: Finish HLS parser, SCTE-35, error handling

## Documentation

- **Architecture**: `docs/ARCHITECTURE.md`
- **API Spec**: `docs/API_SPECIFICATION.md`
- **Roadmap**: `docs/ROADMAP.md`
- **Implementation Guide**: `golang-ssai/PHASE2_IMPLEMENTATION.md`

---

**Status**: Phase 2 structure complete. Ready for Go installation and testing!

