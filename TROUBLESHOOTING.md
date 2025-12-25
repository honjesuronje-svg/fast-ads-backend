# Troubleshooting Guide

## Issue: Getting 404 instead of 401 for Invalid API Key

### Problem
When testing with an invalid API key, you get HTTP 404 (Not Found) instead of HTTP 401 (Unauthorized).

### Root Cause
The 404 error indicates the route isn't being matched at all. This usually means:
1. Laravel server is not running
2. Routes are not properly loaded
3. Route cache needs to be cleared

### Solution

#### 1. Make sure Laravel server is running

```bash
cd /home/lamkapro/fast-ads-backend/laravel-backend
php artisan serve --host=127.0.0.1 --port=8000
```

Keep this terminal open. The server should output:
```
INFO  Server running on [http://127.0.0.1:8000]
```

#### 2. Clear all caches

```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

#### 3. Verify routes are registered

```bash
php artisan route:list --path=api
```

You should see:
```
POST       api/v1/ads/decision
GET|HEAD   api/v1/ads/vast/{tenant_slug}/{channel_slug}
GET|HEAD   api/v1/ads/vmap/{tenant_slug}/{channel_slug}
POST       api/v1/tracking/events
```

#### 4. Test the endpoint

In a **new terminal**, test the endpoint:

```bash
curl -v -H "X-API-Key: invalid_key" \
  -X POST http://127.0.0.1:8000/api/v1/ads/decision \
  -H "Content-Type: application/json" \
  -d '{"tenant_id": 1, "channel": "news", "ad_break_id": "test", "position": "pre-roll", "duration_seconds": 30}'
```

**Expected Result:**
- HTTP 401 with JSON response:
```json
{
  "success": false,
  "error": {
    "code": "INVALID_API_KEY",
    "message": "Invalid or inactive API key"
  }
}
```

**If you still get 404:**
- Check if server is actually running: `ps aux | grep "artisan serve"`
- Check server logs in the terminal where `php artisan serve` is running
- Verify the URL is correct: `http://127.0.0.1:8000/api/v1/ads/decision`
- Make sure you're using POST method (not GET)

#### 5. Verify database and migrations

If routes work but you get other errors:

```bash
# Check if database exists
php artisan tinker
>>> \DB::connection()->getDatabaseName()

# Run migrations if needed
php artisan migrate

# Seed sample data
php artisan db:seed
```

### Quick Test Script

```bash
#!/bin/bash
BASE_URL="http://127.0.0.1:8000"

echo "Testing API endpoint..."
response=$(curl -s -w "\n%{http_code}" \
  -H "X-API-Key: invalid_key" \
  -X POST "$BASE_URL/api/v1/ads/decision" \
  -H "Content-Type: application/json" \
  -d '{"tenant_id": 1, "channel": "news", "ad_break_id": "test", "position": "pre-roll", "duration_seconds": 30}')

http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | sed '$d')

echo "HTTP Status: $http_code"
echo "Response:"
echo "$body" | jq '.' 2>/dev/null || echo "$body"

if [ "$http_code" = "401" ]; then
    echo "✅ SUCCESS: Got 401 as expected"
elif [ "$http_code" = "404" ]; then
    echo "❌ ERROR: Got 404 - Route not found"
    echo "   Make sure server is running: php artisan serve"
else
    echo "⚠️  WARNING: Got $http_code (expected 401)"
fi
```

### Common Issues

1. **Server not running**: Start with `php artisan serve`
2. **Wrong port**: Default is 8000, check if port is in use
3. **Route cache**: Clear with `php artisan route:clear`
4. **Wrong URL**: Must be `/api/v1/ads/decision` (not `/api/ads/decision`)
5. **Wrong method**: Must be POST (not GET)

### Next Steps

Once you get 401 for invalid API key:
1. Test with valid API key (from seeder: `test_api_key_123`)
2. Run full test suite: `./tests/api-test.sh http://127.0.0.1:8000`
3. Check API documentation: `docs/API_SPECIFICATION.md`

