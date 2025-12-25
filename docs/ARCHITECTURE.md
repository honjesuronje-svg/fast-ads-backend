# FAST Ads Backend - System Architecture

## High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           OTT PLATFORMS (Clients)                            │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐      │
│  │  Mobile  │  │Android TV│  │   STB    │  │ Smart TV │  │   Web    │      │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘      │
└───────┼─────────────┼─────────────┼─────────────┼─────────────┼─────────────┘
        │             │             │             │             │
        │  HLS Manifest Requests    │             │             │
        │  /fast/{tenant}/{channel}.m3u8          │             │
        └───────────────────────────┴─────────────┴─────────────┘
                                    │
                                    ▼
        ┌───────────────────────────────────────────────────────┐
        │              CDN / Load Balancer                       │
        │         (nginx / CloudFlare / AWS CloudFront)          │
        └───────────────────────────┬───────────────────────────┘
                                    │
                                    ▼
        ┌───────────────────────────────────────────────────────┐
        │         GOLANG SSAI SERVICE (Data Plane)               │
        │  ┌─────────────────────────────────────────────────┐  │
        │  │  • HLS Manifest Parser                           │  │
        │  │  • SCTE-35 Cue Detection                         │  │
        │  │  • Ad Break Detection                            │  │
        │  │  • Manifest Manipulation                         │  │
        │  │  • Ad Stitching                                  │  │
        │  │  • Session Management                            │  │
        │  │  • Redis Cache (manifests, ads)                  │  │
        │  └─────────────────────────────────────────────────┘  │
        │                                                        │
        │  Endpoints:                                            │
        │  • GET  /fast/{tenant}/{channel}.m3u8                 │
        │  • GET  /fast/{tenant}/{channel}/{segment}.ts         │
        │  • POST /tracking/impression                          │
        │  • POST /tracking/quartile                            │
        │  • POST /tracking/complete                            │
        └───────────────────────────┬───────────────────────────┘
                                    │
                                    │ HTTP/JSON
                                    │ POST /api/ads/decision
                                    │ GET  /api/ads/vmap/{tenant}/{channel}
                                    │ POST /api/tracking/events
                                    ▼
        ┌───────────────────────────────────────────────────────┐
        │      LARAVEL API (Control Plane)                      │
        │  ┌─────────────────────────────────────────────────┐  │
        │  │  • Tenant Management                             │  │
        │  │  • Channel Management                            │  │
        │  │  • Ads Inventory & Campaigns                     │  │
        │  │  • Ad Rules Engine (geo, device, time)           │  │
        │  │  • Ad Pod Logic                                  │  │
        │  │  • VAST / VMAP Generation                        │  │
        │  │  • Reporting & Analytics                         │  │
        │  │  • API Key Authentication                         │  │
        │  └─────────────────────────────────────────────────┘  │
        │                                                        │
        │  Database: PostgreSQL / MySQL                         │
        │  Cache: Redis                                         │
        └───────────────────────────┬───────────────────────────┘
                                    │
                                    │ (Future: Direct Integration)
                                    ▼
        ┌───────────────────────────────────────────────────────┐
        │         EXTERNAL AD SERVERS (Optional)                 │
        │  ┌──────────┐  ┌──────────┐  ┌──────────┐            │
        │  │ Google   │  │  FreeWheel│  │  Others  │            │
        │  │ AdX      │  │          │  │          │            │
        │  └──────────┘  └──────────┘  └──────────┘            │
        └───────────────────────────────────────────────────────┘
```

## Component Responsibilities

### 1. OTT Platforms (Clients)
- **Role**: Video players requesting HLS manifests
- **Interaction**: Only communicates with Golang SSAI service via CDN
- **Never calls**: Laravel API directly

### 2. CDN / Load Balancer
- **Role**: Routes requests to Golang instances
- **Features**: SSL termination, rate limiting, geographic distribution
- **Routing**: `/fast/*` → Golang service

### 3. Golang SSAI Service (Data Plane)
- **Role**: High-performance manifest manipulation and ad stitching
- **Stateless**: Horizontally scalable, no session storage
- **Key Functions**:
  - Parse incoming HLS manifests
  - Detect ad breaks (SCTE-35 or rule-based)
  - Call Laravel for ad decisions
  - Stitch ads into manifest
  - Cache manifests and ad responses
  - Emit tracking events to Laravel

### 4. Laravel API (Control Plane)
- **Role**: Business logic, ads management, reporting
- **Key Functions**:
  - Multi-tenant isolation
  - Ads inventory management
  - Campaign rules engine
  - VAST/VMAP generation
  - Analytics and reporting
  - API authentication

### 5. External Ad Servers
- **Role**: Third-party ad networks (future integration)
- **Integration**: Via VAST URLs returned by Laravel

## Data Flow: SSAI Request

```
1. Player Request
   Player → CDN → Golang: GET /fast/ott_a/news.m3u8

2. Manifest Retrieval
   Golang → Origin CDN: GET original manifest
   Golang → Redis: Check cache

3. Ad Break Detection
   Golang: Parse manifest, detect SCTE-35 cues or static rules

4. Ad Decision Request
   Golang → Laravel: POST /api/ads/decision
   {
     "tenant_id": "ott_a",
     "channel": "news",
     "ad_break_id": "break_1",
     "duration": 120,
     "position": "pre-roll",
     "geo": "US",
     "device": "android_tv"
   }

5. Ad Decision Response
   Laravel → Golang: 
   {
     "ads": [
       {
         "vast_url": "https://adserver.com/vast.xml",
         "duration": 30,
         "ad_id": "ad_123"
       },
       {
         "vast_url": "https://adserver.com/vast2.xml",
         "duration": 15,
         "ad_id": "ad_456"
       }
     ],
     "total_duration": 45
   }

6. Manifest Stitching
   Golang: Insert ad segments into manifest
   Golang → Redis: Cache stitched manifest

7. Response
   Golang → Player: Return stitched manifest

8. Tracking (Async)
   Player → Golang: POST /tracking/impression (when ad plays)
   Golang → Laravel: POST /api/tracking/events
```

## Data Flow: CSAI Request (Phase 1)

```
1. Player Request
   Player → Laravel: GET /api/ads/vmap/ott_a/news

2. VMAP Generation
   Laravel: Query ads rules, generate VMAP
   Laravel → Player: Return VMAP XML

3. Player Ad Request
   Player → Ad Server: Request ads using VMAP
   Ad Server → Player: Return VAST

4. Tracking
   Player → Laravel: POST /api/tracking/events
```

## Multi-Tenant Isolation

Each OTT client is isolated by:
- **API Key**: Unique per tenant, used by Golang to authenticate with Laravel
- **Tenant ID**: Database-level isolation
- **Channel Namespace**: Channels can have same name but different tenant_id
- **Data Isolation**: All queries filtered by tenant_id

Example:
- OTT_A: `tenant_id=1`, `api_key=abc123`, channels: `news`, `sports`
- OTT_B: `tenant_id=2`, `api_key=def456`, channels: `news`, `sports` (different ads/rules)

## Scalability Considerations

### Horizontal Scaling
- **Golang Service**: Stateless, can scale to N instances behind load balancer
- **Laravel API**: Stateless API, can scale to N instances
- **Database**: Read replicas for reporting queries
- **Redis**: Cluster mode for high availability

### Caching Strategy
- **Manifest Cache**: 5-10 seconds TTL (Redis)
- **Ad Decision Cache**: 30-60 seconds TTL (Redis, keyed by tenant+channel+break)
- **VAST Cache**: 5 minutes TTL (Redis)

### Performance Targets
- **Golang Manifest Response**: < 50ms (cached), < 200ms (uncached)
- **Laravel Ad Decision**: < 100ms
- **Concurrent Requests**: 10,000+ per Golang instance
- **Throughput**: 100,000+ manifests/hour per instance

## Security

1. **API Authentication**: API keys per tenant
2. **Rate Limiting**: Per tenant, per endpoint
3. **HTTPS Only**: All communications encrypted
4. **Input Validation**: Strict validation on all inputs
5. **CORS**: Configured per tenant domain
6. **IP Whitelisting**: Optional per tenant

## Technology Stack

### Control Plane (Laravel)
- **Framework**: Laravel 10+
- **Database**: PostgreSQL 14+ (or MySQL 8+)
- **Cache**: Redis 7+
- **Queue**: Redis Queue (for async tracking)

### Data Plane (Golang)
- **Language**: Go 1.21+
- **HTTP Server**: net/http or Gin/Echo
- **HLS Parser**: Custom or github.com/grafov/m3u8
- **Cache**: Redis client (go-redis)
- **SCTE-35**: Custom parser or library

### Infrastructure
- **Containerization**: Docker + Docker Compose
- **Orchestration**: Kubernetes (production)
- **Monitoring**: Prometheus + Grafana
- **Logging**: ELK Stack or Loki

