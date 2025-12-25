# Quick Start Guide - Fix 404 Error

## Problem
Getting 404 error when testing API endpoints, even though routes are registered.

## Solution

### Step 1: Stop any existing servers on port 8000

```bash
# Kill any process on port 8000
pkill -f "php artisan serve.*8000"
# Or find and kill manually:
lsof -ti:8000 | xargs kill -9
```

### Step 2: Start the server from the correct directory

**Option A: Use the start script**
```bash
cd /home/lamkapro/fast-ads-backend/laravel-backend
./START_SERVER.sh
```

**Option B: Manual start**
```bash
cd /home/lamkapro/fast-ads-backend/laravel-backend

# Clear caches first
php artisan route:clear
php artisan config:clear

# Start server
php artisan serve --host=127.0.0.1 --port=8000
```

### Step 3: Verify server is running

In the terminal where you started the server, you should see:
```
INFO  Server running on [http://127.0.0.1:8000]
```

### Step 4: Test the endpoint (in a NEW terminal)

```bash
curl -H "X-API-Key: invalid_key" \
  -X POST http://127.0.0.1:8000/api/v1/ads/decision \
  -H "Content-Type: application/json" \
  -d '{"tenant_id": 1, "channel": "news", "ad_break_id": "test", "position": "pre-roll", "duration_seconds": 30}'
```

**Expected Result:**
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
1. Make sure you're in the correct directory: `/home/lamkapro/fast-ads-backend/laravel-backend`
2. Verify routes: `php artisan route:list --path=api`
3. Check server logs in the terminal where `php artisan serve` is running
4. Try a different port: `php artisan serve --port=8001` (then use `http://127.0.0.1:8001`)

## Important Notes

- **Keep the server running**: Don't close the terminal where `php artisan serve` is running
- **Use correct URL**: Must be `http://127.0.0.1:8000/api/v1/ads/decision` (not `/api/ads/decision`)
- **Use POST method**: The endpoint requires POST, not GET
- **Check directory**: Make sure you're starting the server from `laravel-backend` directory

## Troubleshooting

### Server won't start
```bash
# Check if PHP is installed
php -v

# Check if Laravel is set up
php artisan --version

# Check if .env exists
ls -la .env
```

### Routes not found
```bash
# Clear all caches
php artisan route:clear
php artisan config:clear
php artisan cache:clear

# Verify routes
php artisan route:list
```

### Port already in use
```bash
# Find what's using port 8000
lsof -i :8000

# Kill it
pkill -f "php artisan serve.*8000"

# Or use a different port
php artisan serve --port=8001
```

