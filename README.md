# FAST Ads Backend - Independent OTT Ads Platform

A production-grade, headless FAST (Free Ad-Supported Streaming TV) ads backend that can be integrated into multiple OTT platforms (mobile, Android TV, STB, Smart TV).

## 🎯 Overview

This system provides a clean separation between business logic (Laravel Control Plane) and high-performance streaming logic (Golang Data Plane), making it OTT-agnostic and horizontally scalable.

### Key Features

- **Multi-Tenant**: Isolated OTT clients with API key authentication
- **FAST Support**: Free Ad-Supported Streaming TV channels
- **CSAI & SSAI**: Client-Side and Server-Side Ad Insertion
- **High Performance**: Sub-100ms response times (cached)
- **Scalable**: Stateless services, horizontal scaling
- **Production-Ready**: Docker-based deployment, monitoring, logging

## 📁 Project Structure

```
fast-ads-backend/
├── docs/                    # Documentation
│   ├── ARCHITECTURE.md     # System architecture
│   ├── DATABASE_SCHEMA.md  # Database design
│   ├── API_SPECIFICATION.md # REST API docs
│   └── ROADMAP.md          # Implementation roadmap
├── laravel-backend/         # Control Plane (Laravel)
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Services/
│   │   └── Models/
│   ├── database/migrations/
│   └── routes/
├── golang-ssai/            # Data Plane (Golang)
│   ├── cmd/ssai-service/
│   ├── internal/
│   │   ├── handler/
│   │   ├── parser/
│   │   ├── cache/
│   │   └── client/
│   └── configs/
└── deployment/             # Docker & deployment configs
    ├── docker-compose.yml
    ├── nginx/
    └── README.md
```

## 🏗️ Architecture

### Control Plane (Laravel)
- Tenant & channel management
- Ads inventory & campaigns
- Ad rules engine (geo, device, time)
- VAST/VMAP generation
- Reporting & analytics
- API authentication

### Data Plane (Golang)
- HLS manifest manipulation
- SCTE-35 cue detection
- Ad stitching
- Session handling
- Caching (Redis)
- High-performance request handling

See [ARCHITECTURE.md](docs/ARCHITECTURE.md) for detailed architecture.

## 🚀 Quick Start

### Prerequisites
- Docker 20.10+
- Docker Compose 2.0+
- 4GB+ RAM

### Installation

1. **Clone and navigate:**
```bash
cd /home/lamkapro/fast-ads-backend/deployment
```

2. **Configure environment:**
```bash
cp .env.example .env
# Edit .env with your settings
```

3. **Start services:**
```bash
docker-compose up -d
```

4. **Run migrations:**
```bash
docker-compose exec laravel-api php artisan migrate
```

5. **Create tenant:**
```bash
docker-compose exec laravel-api php artisan tinker
```
```php
\App\Models\Tenant::create([
    'name' => 'Test OTT',
    'slug' => 'test_ott',
    'api_key' => 'test_key_123',
    'api_secret' => bcrypt('secret'),
    'status' => 'active',
]);
```

See [deployment/README.md](deployment/README.md) for detailed deployment guide.

## 📡 API Usage

### Get VMAP (CSAI)
```bash
curl -H "X-API-Key: your_api_key" \
  http://api.fastads.local/api/v1/ads/vmap/test_ott/news
```

### Get Stitched Manifest (SSAI)
```bash
curl http://ssai.fastads.local/fast/test_ott/news.m3u8
```

### Ad Decision (Internal - Golang → Laravel)
```bash
curl -X POST -H "X-API-Key: your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "channel": "news",
    "ad_break_id": "break_123",
    "position": "mid-roll",
    "duration_seconds": 120,
    "geo": "US",
    "device": "android_tv"
  }' \
  http://api.fastads.local/api/v1/ads/decision
```

See [API_SPECIFICATION.md](docs/API_SPECIFICATION.md) for complete API documentation.

## 🗄️ Database Schema

The system uses PostgreSQL/MySQL with the following core tables:
- `tenants` - OTT client information
- `channels` - FAST channels
- `ad_campaigns` - Ad campaigns
- `ads` - Ad creatives
- `ad_rules` - Targeting rules
- `ad_pod_configs` - Ad pod configurations
- `tracking_events` - Ad tracking events

See [DATABASE_SCHEMA.md](docs/DATABASE_SCHEMA.md) for complete schema.

## 🔧 Configuration

### Laravel
- Environment variables in `.env`
- Database: PostgreSQL/MySQL
- Cache: Redis

### Golang
- Configuration file: `configs/config.yaml`
- Laravel API URL
- Redis connection
- Cache TTLs

## 📊 Monitoring

### Health Checks
```bash
# Laravel API
curl http://api.fastads.local/health

# Golang SSAI
curl http://ssai.fastads.local/health
```

### Metrics
- Prometheus metrics endpoint: `/metrics`
- Response times
- Request rates
- Error rates
- Cache hit rates

## 🔒 Security

- API key authentication per tenant
- Rate limiting per tenant
- HTTPS only (production)
- Input validation
- CORS configuration
- IP whitelisting (optional)

## 📈 Scaling

### Horizontal Scaling
```bash
# Scale Golang SSAI
docker-compose up -d --scale golang-ssai=3

# Scale Laravel API
docker-compose up -d --scale laravel-api=2
```

### Performance Targets
- **Response Time**: < 100ms (p95)
- **Throughput**: 100,000+ requests/hour
- **Uptime**: 99.9%+
- **Concurrent Requests**: 10,000+ per instance

## 🗺️ Roadmap

### Phase 1: CSAI FAST (Weeks 1-4)
- Basic Laravel API
- VMAP/VAST generation
- Multi-tenant support
- Basic reporting

### Phase 2: SSAI FAST (Weeks 5-10)
- Golang SSAI service
- HLS manifest manipulation
- Ad stitching
- Integration testing

### Phase 3: Scale & Reporting (Weeks 11-16)
- Advanced reporting
- Performance optimization
- Production hardening
- Monitoring & alerting

See [ROADMAP.md](docs/ROADMAP.md) for detailed roadmap.

## 🧪 Testing

```bash
# Run Laravel tests
docker-compose exec laravel-api php artisan test

# Run Golang tests
cd golang-ssai
go test ./...
```

## 📝 Documentation

- [Architecture](docs/ARCHITECTURE.md)
- [Database Schema](docs/DATABASE_SCHEMA.md)
- [API Specification](docs/API_SPECIFICATION.md)
- [Roadmap](docs/ROADMAP.md)
- [Deployment Guide](deployment/README.md)

## 🤝 Contributing

This is a production system. Follow these guidelines:
1. Write tests for new features
2. Update documentation
3. Follow code style guidelines
4. Review before merging

## 📄 License

Proprietary - Internal use only

## 🆘 Support

For issues or questions:
1. Check documentation
2. Review logs: `docker-compose logs`
3. Check health endpoints
4. Contact system administrator

## 🎯 Key Decisions

### Why Laravel for Control Plane?
- Rapid development
- Rich ecosystem
- Excellent ORM
- Built-in features (auth, validation, queues)

### Why Golang for Data Plane?
- High performance
- Low latency
- Excellent concurrency
- Small memory footprint

### Why Separate Services?
- Independent scaling
- Technology flexibility
- Clear separation of concerns
- Easier maintenance

---

**Built for production. Designed for scale. Independent from OTT apps.**

