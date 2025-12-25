# Deployment Guide

## Prerequisites

- Docker 20.10+
- Docker Compose 2.0+
- 4GB+ RAM available
- 10GB+ disk space

## Quick Start

1. **Clone and navigate to deployment directory:**
```bash
cd /home/lamkapro/fast-ads-backend/deployment
```

2. **Copy environment file:**
```bash
cp .env.example .env
```

3. **Edit `.env` file with your settings:**
```bash
nano .env
```

4. **Start all services:**
```bash
docker-compose up -d
```

5. **Run Laravel migrations:**
```bash
docker-compose exec laravel-api php artisan migrate
```

6. **Create initial tenant:**
```bash
docker-compose exec laravel-api php artisan tinker
```
```php
$tenant = \App\Models\Tenant::create([
    'name' => 'Test OTT',
    'slug' => 'test_ott',
    'api_key' => 'test_api_key_123',
    'api_secret' => bcrypt('secret'),
    'status' => 'active',
]);
```

## Service URLs

- **Laravel API**: http://api.fastads.local
- **Golang SSAI**: http://ssai.fastads.local
- **PostgreSQL**: localhost:5432
- **Redis**: localhost:6379

## Health Checks

```bash
# Laravel API
curl http://api.fastads.local/health

# Golang SSAI
curl http://ssai.fastads.local/health
```

## Scaling

### Scale Golang SSAI Service
```bash
docker-compose up -d --scale golang-ssai=3
```

### Scale Laravel API
```bash
docker-compose up -d --scale laravel-api=2
```

Update nginx upstream configuration to include new instances.

## Production Deployment

### 1. Use Production-Grade Database
- Use managed PostgreSQL (AWS RDS, Google Cloud SQL, etc.)
- Update `DB_HOST` in `.env`

### 2. Use Redis Cluster
- Use managed Redis (AWS ElastiCache, Google Memorystore, etc.)
- Update `REDIS_HOST` in `.env`

### 3. SSL/TLS
- Add SSL certificates to nginx
- Update nginx config to listen on port 443
- Use Let's Encrypt or commercial certificates

### 4. Monitoring
- Add Prometheus exporters
- Set up Grafana dashboards
- Configure alerting

### 5. Logging
- Use centralized logging (ELK, Loki, etc.)
- Configure log rotation
- Set up log aggregation

## Kubernetes Deployment (Future)

See `k8s/` directory for Kubernetes manifests (to be created).

## Troubleshooting

### Check logs
```bash
docker-compose logs -f laravel-api
docker-compose logs -f golang-ssai
```

### Restart services
```bash
docker-compose restart laravel-api
docker-compose restart golang-ssai
```

### Database connection issues
```bash
docker-compose exec postgres psql -U fast_ads_user -d fast_ads
```

