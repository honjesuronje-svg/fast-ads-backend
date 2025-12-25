# FAST Ads Backend - Setup Guide

## Prerequisites

- PHP 8.2+
- Composer 2.0+
- PostgreSQL 14+ (or MySQL 8+)
- Redis 7+
- Node.js 18+ (optional, for frontend if needed)

## Step 1: Install Dependencies

```bash
cd /home/lamkapro/fast-ads-backend/laravel-backend
composer install
```

## Step 2: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Edit `.env` file with your database and Redis settings:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fast_ads
DB_USERNAME=fast_ads_user
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Step 3: Database Setup

```bash
# Create database (PostgreSQL example)
createdb fast_ads

# Or MySQL:
# mysql -u root -p
# CREATE DATABASE fast_ads;

# Run migrations
php artisan migrate

# Seed sample data
php artisan db:seed
```

## Step 4: Start Development Server

```bash
php artisan serve
```

The API will be available at: `http://localhost:8000`

## Step 5: Test API Endpoints

### Using the test script:

```bash
cd /home/lamkapro/fast-ads-backend/tests
chmod +x api-test.sh
./api-test.sh http://localhost:8000
```

### Using curl:

```bash
# Test Ad Decision API
curl -X POST http://localhost:8000/api/v1/ads/decision \
  -H "X-API-Key: test_api_key_123" \
  -H "Content-Type: application/json" \
  -d '{
    "tenant_id": 1,
    "channel": "news",
    "ad_break_id": "break_123",
    "position": "pre-roll",
    "duration_seconds": 30,
    "geo": "US",
    "device": "android_tv"
  }'

# Test VMAP Generation
curl -H "X-API-Key: test_api_key_123" \
  http://localhost:8000/api/v1/ads/vmap/ott_a/news?geo=US

# Test VAST Generation
curl -H "X-API-Key: test_api_key_123" \
  http://localhost:8000/api/v1/ads/vast/ott_a/news?position=pre-roll&geo=US
```

### Using REST Client (VS Code):

Open `tests/api-test.http` in VS Code with REST Client extension installed.

## Step 6: Verify Setup

1. **Check database tables:**
```bash
php artisan tinker
>>> \DB::table('tenants')->count()
```

2. **Test API health:**
```bash
curl http://localhost:8000/
```

3. **Check logs:**
```bash
tail -f storage/logs/laravel.log
```

## Docker Setup (Alternative)

If you prefer Docker:

```bash
cd /home/lamkapro/fast-ads-backend/deployment
docker-compose up -d
```

See `deployment/README.md` for details.

## Troubleshooting

### Database Connection Issues

```bash
# Test PostgreSQL connection
psql -h 127.0.0.1 -U fast_ads_user -d fast_ads

# Clear config cache
php artisan config:clear
```

### Migration Issues

```bash
# Reset database (WARNING: deletes all data)
php artisan migrate:fresh --seed

# Rollback last migration
php artisan migrate:rollback
```

### API Key Issues

The seeder creates a tenant with API key: `test_api_key_123`

To create a new tenant:
```bash
php artisan tinker
>>> $tenant = \App\Models\Tenant::create([
    'name' => 'My OTT',
    'slug' => 'my_ott',
    'api_key' => 'my_api_key',
    'api_secret' => bcrypt('secret'),
    'status' => 'active',
]);
```

## Next Steps

1. Review API documentation: `docs/API_SPECIFICATION.md`
2. Follow roadmap: `docs/ROADMAP.md`
3. Set up Golang SSAI service (Phase 2)
4. Configure production environment

## Production Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Use strong database passwords
- [ ] Configure Redis persistence
- [ ] Set up SSL/TLS certificates
- [ ] Configure rate limiting
- [ ] Set up monitoring and logging
- [ ] Configure backup procedures
- [ ] Review security settings

