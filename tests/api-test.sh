#!/bin/bash

# FAST Ads Backend - API Test Script
# Usage: ./api-test.sh [base_url]

BASE_URL="${1:-http://localhost:8000}"
API_KEY="test_api_key_123"

echo "=========================================="
echo "FAST Ads Backend - API Tests"
echo "Base URL: $BASE_URL"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    local description=$4
    
    echo -n "Testing: $description ... "
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "\n%{http_code}" -H "X-API-Key: $API_KEY" "$BASE_URL$endpoint")
    elif [ "$method" = "POST" ]; then
        response=$(curl -s -w "\n%{http_code}" -X POST \
            -H "X-API-Key: $API_KEY" \
            -H "Content-Type: application/json" \
            -d "$data" \
            "$BASE_URL$endpoint")
    fi
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" -ge 200 ] && [ "$http_code" -lt 300 ]; then
        echo -e "${GREEN}✓${NC} (HTTP $http_code)"
        echo "$body" | jq '.' 2>/dev/null || echo "$body"
    else
        echo -e "${RED}✗${NC} (HTTP $http_code)"
        echo "$body" | jq '.' 2>/dev/null || echo "$body"
    fi
    echo ""
}

echo "1. Testing Ad Decision API"
echo "---------------------------"
test_endpoint "POST" "/api/v1/ads/decision" '{
    "tenant_id": 1,
    "channel": "news",
    "ad_break_id": "break_123",
    "position": "pre-roll",
    "duration_seconds": 30,
    "geo": "US",
    "device": "android_tv",
    "timestamp": "2024-01-15T10:30:00Z"
}' "Ad Decision (pre-roll)"

test_endpoint "POST" "/api/v1/ads/decision" '{
    "tenant_id": 1,
    "channel": "news",
    "ad_break_id": "break_456",
    "position": "mid-roll",
    "duration_seconds": 120,
    "geo": "US",
    "device": "android_tv"
}' "Ad Decision (mid-roll)"

echo ""
echo "2. Testing VMAP Generation (CSAI)"
echo "----------------------------------"
test_endpoint "GET" "/api/v1/ads/vmap/ott_a/news?geo=US&device=android_tv" "" "VMAP Generation"

echo ""
echo "3. Testing VAST Generation (CSAI)"
echo "----------------------------------"
test_endpoint "GET" "/api/v1/ads/vast/ott_a/news?position=pre-roll&geo=US" "" "VAST Generation (pre-roll)"

test_endpoint "GET" "/api/v1/ads/vast/ott_a/news?position=mid-roll&geo=US" "" "VAST Generation (mid-roll)"

echo ""
echo "4. Testing Tracking Events"
echo "---------------------------"
test_endpoint "POST" "/api/v1/tracking/events" '{
    "events": [
        {
            "tenant_id": 1,
            "channel_id": 1,
            "ad_id": 1,
            "event_type": "impression",
            "session_id": "session_abc123",
            "device_type": "android_tv",
            "geo_country": "US",
            "ip_address": "192.168.1.1",
            "user_agent": "Mozilla/5.0",
            "timestamp": "2024-01-15T10:30:00Z"
        }
    ]
}' "Tracking Event (impression)"

test_endpoint "POST" "/api/v1/tracking/events" '{
    "events": [
        {
            "tenant_id": 1,
            "ad_id": 1,
            "event_type": "complete",
            "timestamp": "2024-01-15T10:30:30Z"
        }
    ]
}' "Tracking Event (complete)"

echo ""
echo "5. Testing Error Cases"
echo "----------------------"
echo -n "Testing: Invalid API Key ... "
response=$(curl -s -w "\n%{http_code}" -H "X-API-Key: invalid_key" "$BASE_URL/api/v1/ads/decision" \
    -X POST -H "Content-Type: application/json" -d '{"tenant_id": 1, "channel": "news", "ad_break_id": "test", "position": "pre-roll", "duration_seconds": 30}')
http_code=$(echo "$response" | tail -n1)
body=$(echo "$response" | sed '$d')
if [ "$http_code" = "401" ]; then
    echo -e "${GREEN}✓${NC} (HTTP $http_code - Unauthorized as expected)"
elif [ "$http_code" = "404" ]; then
    echo -e "${YELLOW}⚠${NC} (HTTP $http_code - Route not found, check if server is running)"
    echo "   Make sure to run: cd laravel-backend && php artisan serve"
else
    echo -e "${RED}✗${NC} (HTTP $http_code - Expected 401)"
    echo "$body" | jq '.' 2>/dev/null || echo "$body" | head -3
fi
echo ""

echo "=========================================="
echo "Tests completed!"
echo "=========================================="

