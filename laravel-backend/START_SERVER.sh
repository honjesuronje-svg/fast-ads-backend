#!/bin/bash

# FAST Ads Backend - Start Server Script
# This script ensures the server is running from the correct directory

cd "$(dirname "$0")"

echo "=========================================="
echo "FAST Ads Backend - Starting Server"
echo "=========================================="
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ ERROR: .env file not found!"
    echo "   Run: cp .env.example .env && php artisan key:generate"
    exit 1
fi

# Check if vendor exists
if [ ! -d vendor ]; then
    echo "❌ ERROR: vendor directory not found!"
    echo "   Run: composer install"
    exit 1
fi

# Clear caches
echo "Clearing caches..."
php artisan route:clear > /dev/null 2>&1
php artisan config:clear > /dev/null 2>&1

# Check if port 8000 is in use
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null 2>&1 ; then
    echo "⚠️  WARNING: Port 8000 is already in use!"
    echo "   Killing existing process..."
    pkill -f "php artisan serve.*8000" 2>/dev/null
    sleep 2
fi

# Start server
echo "Starting Laravel development server..."
echo "Server will run on: http://127.0.0.1:8000"
echo ""
echo "Press Ctrl+C to stop the server"
echo "=========================================="
echo ""

php artisan serve --host=127.0.0.1 --port=8000

