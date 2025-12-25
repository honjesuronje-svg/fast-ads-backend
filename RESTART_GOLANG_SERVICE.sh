#!/bin/bash
# Restart Golang SSAI Service

cd /home/lamkapro/fast-ads-backend/golang-ssai

echo "Stopping ssai-service..."
pkill ssai-service
sleep 2

echo "Starting ssai-service..."
nohup ./bin/ssai-service > /tmp/ssai-service.log 2>&1 &

sleep 2

echo "Checking service status..."
if ps aux | grep -v grep | grep ssai-service > /dev/null; then
    echo "✅ Service started successfully"
    echo "PID: $(pgrep ssai-service)"
else
    echo "❌ Service failed to start"
    echo "Check logs: tail -f /tmp/ssai-service.log"
    exit 1
fi

echo ""
echo "Service restarted with new config!"
echo "Base URL: https://doubleclick.wkkworld.com/api/v1"
echo ""
echo "Test tracking events:"
echo "  php artisan reports:check"

