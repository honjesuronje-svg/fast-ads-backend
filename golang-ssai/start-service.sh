#!/bin/bash
# Start SSAI Service Script

cd "$(dirname "$0")"

# Kill existing service
pkill -f ssai-service || true
sleep 1

# Start service
CONFIG_PATH=configs/config.yaml nohup ./bin/ssai-service > /tmp/ssai-service.log 2>&1 &

sleep 2

# Check if running
if ps aux | grep -v grep | grep ssai-service > /dev/null; then
    echo "✅ SSAI Service started successfully"
    echo "📋 PID: $(pgrep -f ssai-service)"
    echo "📝 Logs: tail -f /tmp/ssai-service.log"
    echo "🔍 Health: curl http://127.0.0.1:8080/health"
else
    echo "❌ Failed to start SSAI Service"
    echo "📝 Check logs: cat /tmp/ssai-service.log"
    exit 1
fi

