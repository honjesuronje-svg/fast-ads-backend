# REST API Specification - Laravel Control Plane

## Base URL
```
https://api.fastads.example.com/api/v1
```

## Authentication
All requests require API key authentication via header:
```
X-API-Key: {api_key}
```

## Response Format
All responses are JSON:
```json
{
  "success": true,
  "data": { ... },
  "message": "Success message"
}
```

Error response:
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Error message",
    "details": { ... }
  }
}
```

## Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Rate Limit Exceeded
- `500` - Server Error

---

## 1. Ads Decision API

### POST /ads/decision
Called by Golang service to get ads for an ad break.

**Request Headers:**
```
X-API-Key: {api_key}
Content-Type: application/json
```

**Request Body:**
```json
{
  "tenant_id": 1,
  "channel": "news",
  "ad_break_id": "break_123",
  "position": "pre-roll",
  "duration_seconds": 120,
  "geo": "US",
  "device": "android_tv",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "ads": [
      {
        "ad_id": 123,
        "vast_url": "https://adserver.com/vast.xml",
        "duration_seconds": 30,
        "ad_type": "linear",
        "click_through_url": "https://advertiser.com",
        "tracking_urls": {
          "impression": "https://tracker.com/impression?ad_id=123",
          "start": "https://tracker.com/start?ad_id=123",
          "first_quartile": "https://tracker.com/q1?ad_id=123",
          "midpoint": "https://tracker.com/mid?ad_id=123",
          "third_quartile": "https://tracker.com/q3?ad_id=123",
          "complete": "https://tracker.com/complete?ad_id=123"
        }
      },
      {
        "ad_id": 124,
        "vast_url": "https://adserver.com/vast2.xml",
        "duration_seconds": 15,
        "ad_type": "linear"
      }
    ],
    "total_duration_seconds": 45,
    "pod_id": "pod_abc123"
  }
}
```

**Error Responses:**
- `401` - Invalid API key
- `404` - Channel not found
- `422` - Invalid request data

---

## 2. VMAP Generation (CSAI)

### GET /ads/vmap/{tenant_slug}/{channel_slug}
Generate VMAP for client-side ad insertion.

**Request Headers:**
```
X-API-Key: {api_key}
Accept: application/vmap+xml
```

**Query Parameters:**
- `geo` (optional) - Country code (e.g., `US`)
- `device` (optional) - Device type (e.g., `android_tv`)

**Response:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<vmap:VMAP xmlns:vmap="http://www.iab.net/vmap-1.0" version="1.0">
  <vmap:AdBreak timeOffset="start" breakType="linear" breakId="pre-roll">
    <vmap:AdSource id="pre-roll-ad" allowMultipleAds="false" followRedirects="true">
      <vmap:AdTagURI templateType="vast3">
        <![CDATA[https://api.fastads.example.com/api/v1/ads/vast/ott_a/news?position=pre-roll]]>
      </vmap:AdTagURI>
    </vmap:AdSource>
  </vmap:AdBreak>
  <vmap:AdBreak timeOffset="00:06:00" breakType="linear" breakId="mid-roll-1">
    <vmap:AdSource id="mid-roll-ad-1" allowMultipleAds="true" followRedirects="true">
      <vmap:AdTagURI templateType="vast3">
        <![CDATA[https://api.fastads.example.com/api/v1/ads/vast/ott_a/news?position=mid-roll]]>
      </vmap:AdTagURI>
    </vmap:AdSource>
  </vmap:AdBreak>
</vmap:VMAP>
```

---

## 3. VAST Generation

### GET /ads/vast/{tenant_slug}/{channel_slug}
Generate VAST XML for a specific ad break.

**Request Headers:**
```
X-API-Key: {api_key}
Accept: application/vast+xml
```

**Query Parameters:**
- `position` (required) - `pre-roll`, `mid-roll`, or `post-roll`
- `geo` (optional)
- `device` (optional)

**Response:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<VAST version="3.0">
  <Ad id="ad_123">
    <InLine>
      <AdSystem>FAST Ads Platform</AdSystem>
      <AdTitle>Brand Ad</AdTitle>
      <Impression>https://tracker.com/impression?ad_id=123</Impression>
      <Creatives>
        <Creative id="creative_1">
          <Linear>
            <Duration>00:00:30</Duration>
            <MediaFiles>
              <MediaFile delivery="progressive" type="video/mp4" width="1920" height="1080">
                https://cdn.example.com/ads/ad_123.mp4
              </MediaFile>
            </MediaFiles>
            <TrackingEvents>
              <Tracking event="start">https://tracker.com/start?ad_id=123</Tracking>
              <Tracking event="firstQuartile">https://tracker.com/q1?ad_id=123</Tracking>
              <Tracking event="midpoint">https://tracker.com/mid?ad_id=123</Tracking>
              <Tracking event="thirdQuartile">https://tracker.com/q3?ad_id=123</Tracking>
              <Tracking event="complete">https://tracker.com/complete?ad_id=123</Tracking>
            </TrackingEvents>
          </Linear>
        </Creative>
      </Creatives>
    </InLine>
  </Ad>
</VAST>
```

---

## 4. Tracking Events

### POST /tracking/events
Receive tracking events from Golang service or players.

**Request Headers:**
```
X-API-Key: {api_key}
Content-Type: application/json
```

**Request Body:**
```json
{
  "events": [
    {
      "tenant_id": 1,
      "channel_id": 5,
      "ad_id": 123,
      "event_type": "impression",
      "session_id": "session_abc123",
      "device_type": "android_tv",
      "geo_country": "US",
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0...",
      "timestamp": "2024-01-15T10:30:00Z",
      "metadata": {
        "player_version": "1.2.3",
        "video_position": 120
      }
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "processed": 1,
    "failed": 0
  }
}
```

---

## 5. Tenant Management

### GET /tenants
List all tenants (admin only).

### GET /tenants/{id}
Get tenant details.

### POST /tenants
Create new tenant.

**Request Body:**
```json
{
  "name": "OTT Platform A",
  "slug": "ott_a",
  "status": "active",
  "allowed_domains": ["https://app.otta.com"],
  "rate_limit_per_minute": 1000
}
```

### PUT /tenants/{id}
Update tenant.

### DELETE /tenants/{id}
Delete tenant (soft delete).

---

## 6. Channel Management

### GET /channels
List channels for authenticated tenant.

**Query Parameters:**
- `status` (optional) - Filter by status

**Response:**
```json
{
  "success": true,
  "data": {
    "channels": [
      {
        "id": 1,
        "name": "News Channel",
        "slug": "news",
        "hls_manifest_url": "https://cdn.example.com/news/master.m3u8",
        "ad_break_strategy": "static",
        "ad_break_interval_seconds": 360,
        "status": "active"
      }
    ]
  }
}
```

### GET /channels/{id}
Get channel details.

### POST /channels
Create new channel.

**Request Body:**
```json
{
  "name": "Sports Channel",
  "slug": "sports",
  "description": "24/7 Sports coverage",
  "hls_manifest_url": "https://cdn.example.com/sports/master.m3u8",
  "ad_break_strategy": "static",
  "ad_break_interval_seconds": 360
}
```

### PUT /channels/{id}
Update channel.

### DELETE /channels/{id}
Delete channel.

---

## 7. Ad Campaign Management

### GET /campaigns
List campaigns for authenticated tenant.

**Query Parameters:**
- `status` (optional)
- `start_date` (optional)
- `end_date` (optional)

### GET /campaigns/{id}
Get campaign details.

### POST /campaigns
Create new campaign.

**Request Body:**
```json
{
  "name": "Q1 2024 Campaign",
  "description": "First quarter advertising",
  "start_date": "2024-01-01T00:00:00Z",
  "end_date": "2024-03-31T23:59:59Z",
  "budget": 100000.00,
  "priority": 10
}
```

### PUT /campaigns/{id}
Update campaign.

### DELETE /campaigns/{id}
Delete campaign.

---

## 8. Ad Management

### GET /ads
List ads for authenticated tenant.

**Query Parameters:**
- `campaign_id` (optional)
- `status` (optional)

### GET /ads/{id}
Get ad details.

### POST /ads
Create new ad.

**Request Body:**
```json
{
  "campaign_id": 1,
  "name": "Brand Ad 1",
  "vast_url": "https://adserver.com/vast.xml",
  "duration_seconds": 30,
  "ad_type": "linear",
  "click_through_url": "https://advertiser.com"
}
```

### PUT /ads/{id}
Update ad.

### DELETE /ads/{id}
Delete ad.

---

## 9. Ad Rules Management

### GET /ads/{ad_id}/rules
List rules for an ad.

### POST /ads/{ad_id}/rules
Create ad rule.

**Request Body:**
```json
{
  "rule_type": "geo",
  "rule_operator": "in",
  "rule_value": ["US", "CA", "MX"],
  "priority": 0
}
```

### DELETE /ads/{ad_id}/rules/{id}
Delete rule.

---

## 10. Ad Pod Configuration

### GET /ad-pods
List ad pod configs for authenticated tenant.

### GET /ad-pods/{id}
Get ad pod config.

### POST /ad-pods
Create ad pod config.

**Request Body:**
```json
{
  "channel_id": 1,
  "position_type": "mid-roll",
  "min_ads": 2,
  "max_ads": 4,
  "max_duration_seconds": 120,
  "fill_strategy": "best_effort"
}
```

---

## 11. Reporting & Analytics

### GET /reports/impressions
Get impression reports.

**Query Parameters:**
- `start_date` (required) - ISO 8601
- `end_date` (required) - ISO 8601
- `channel_id` (optional)
- `ad_id` (optional)
- `group_by` (optional) - `day`, `hour`, `channel`, `ad`

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_impressions": 150000,
      "total_completes": 120000,
      "completion_rate": 0.80
    },
    "breakdown": [
      {
        "date": "2024-01-15",
        "impressions": 5000,
        "completes": 4000
      }
    ]
  }
}
```

### GET /reports/revenue
Get revenue reports (if applicable).

### GET /reports/performance
Get performance metrics.

---

## Rate Limiting

- Default: 1000 requests/minute per tenant
- Ad Decision API: 5000 requests/minute (higher limit)
- Tracking Events: 10000 requests/minute (highest limit)

Rate limit headers:
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1642248000
```

---

## Webhooks (Future)

### POST /webhooks
Register webhook for events.

**Request Body:**
```json
{
  "url": "https://client.com/webhook",
  "events": ["ad.impression", "ad.complete", "campaign.completed"]
}
```

---

## Error Codes

| Code | Description |
|------|-------------|
| `INVALID_API_KEY` | API key is missing or invalid |
| `TENANT_NOT_FOUND` | Tenant does not exist |
| `CHANNEL_NOT_FOUND` | Channel does not exist |
| `CAMPAIGN_NOT_FOUND` | Campaign does not exist |
| `AD_NOT_FOUND` | Ad does not exist |
| `VALIDATION_ERROR` | Request validation failed |
| `RATE_LIMIT_EXCEEDED` | Too many requests |
| `INSUFFICIENT_ADS` | No ads available for criteria |
| `INTERNAL_ERROR` | Server error |

