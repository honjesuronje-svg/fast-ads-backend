#!/bin/bash

# Test script for Golang SSAI service

BASE_URL="${1:-http://localhost:8080}"

echo "=========================================="
echo "Golang SSAI Service - Test Script"
echo "Base URL: $BASE_URL"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

test_endpoint() {
    local method=$1
    local endpoint=$2
    local description=$3
    
    echo -n "Testing: $description ... "
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "\n%{http_code}" "$BASE_URL$endpoint")
    fi
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" -ge 200 ] && [ "$http_code" -lt 300 ]; then
        echo -e "${GREEN}✓${NC} (HTTP $http_code)"
        if [ -n "$body" ]; then
            echo "$body" | head -5
        fi
    else
        echo -e "${RED}✗${NC} (HTTP $http_code)"
        echo "$body" | head -3
    fi
    echo ""
}

echo "1. Health Check"
echo "----------------"
test_endpoint "GET" "/health" "Health endpoint"

echo ""
echo "2. Manifest Endpoint"
echo "---------------------"
test_endpoint "GET" "/fast/ott_a/news.m3u8" "Manifest request (requires origin CDN)"

echo ""
echo "3. Metrics"
echo "----------"
test_endpoint "GET" "/metrics" "Prometheus metrics"

echo ""
echo "=========================================="
echo "Tests completed!"
echo "=========================================="
echo ""
echo "Note: Manifest endpoint requires:"
echo "  - Origin CDN configured in config.yaml"
echo "  - Valid HLS manifest at origin URL"
echo "  - Laravel API running for ad decisions"

