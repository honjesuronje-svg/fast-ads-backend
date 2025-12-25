#!/bin/bash

# Script to restart Golang SSAI service

SERVICE_DIR="/home/lamkapro/fast-ads-backend/golang-ssai"
SERVICE_BIN="$SERVICE_DIR/bin/ssai-service"
LOG_FILE="/tmp/ssai-service.log"

echo "🛑 Stopping SSAI service..."
pkill -f ssai-service
sleep 2

if pgrep -f ssai-service > /dev/null; then
    echo "⚠️  Warning: Service still running, force killing..."
    pkill -9 -f ssai-service
    sleep 1
fi

echo "🚀 Starting SSAI service..."
cd "$SERVICE_DIR" || exit 1

if [ ! -f "$SERVICE_BIN" ]; then
    echo "❌ Error: Service binary not found at $SERVICE_BIN"
    echo "   Please run 'make build' first"
    exit 1
fi

nohup "$SERVICE_BIN" > "$LOG_FILE" 2>&1 &
SERVICE_PID=$!

sleep 2

if ps -p $SERVICE_PID > /dev/null; then
    echo "✅ Service started successfully (PID: $SERVICE_PID)"
    echo "📋 Log file: $LOG_FILE"
    echo ""
    echo "To view logs: tail -f $LOG_FILE"
    echo "To check status: ps aux | grep ssai-service"
else
    echo "❌ Error: Service failed to start"
    echo "Check logs: tail -20 $LOG_FILE"
    exit 1
fi

