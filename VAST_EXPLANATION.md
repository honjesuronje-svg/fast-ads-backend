# Penjelasan VAST (Video Ad Serving Template)

## Apa itu VAST?

**VAST** (Video Ad Serving Template) adalah standar XML yang digunakan untuk komunikasi antara video player dan ad server. VAST memungkinkan video player untuk:
- Request iklan dari ad server
- Menerima informasi tentang iklan (URL video, duration, tracking URLs)
- Menampilkan iklan ke user
- Melaporkan tracking events (impression, quartile, complete, click)

## Format VAST URL

```
https://adserver.com/vast2.xml
```

Ini adalah URL ke file XML yang berisi informasi iklan. Format:
- `https://adserver.com` = Ad server domain
- `vast2.xml` = VAST XML file (bisa juga `vast.xml`, `vast3.xml`, dll)

## Versi VAST

- **VAST 2.0**: Format dasar, masih banyak digunakan
- **VAST 3.0**: Menambahkan support untuk non-linear ads, companion ads
- **VAST 4.0**: Latest version dengan improved error handling, ad pods

## Struktur VAST XML

### Contoh VAST 2.0 Response:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<VAST version="2.0">
  <Ad id="12345">
    <InLine>
      <AdSystem version="1.0">AdServer Name</AdSystem>
      <AdTitle>Coca Cola Commercial</AdTitle>
      <Description>30 second commercial</Description>
      <Impression>https://adserver.com/track/impression?id=12345</Impression>
      
      <Creatives>
        <Creative id="creative1" sequence="1">
          <Linear>
            <Duration>00:00:30</Duration>
            <TrackingEvents>
              <Tracking event="start">https://adserver.com/track/start?id=12345</Tracking>
              <Tracking event="firstQuartile">https://adserver.com/track/firstQuartile?id=12345</Tracking>
              <Tracking event="midpoint">https://adserver.com/track/midpoint?id=12345</Tracking>
              <Tracking event="thirdQuartile">https://adserver.com/track/thirdQuartile?id=12345</Tracking>
              <Tracking event="complete">https://adserver.com/track/complete?id=12345</Tracking>
            </TrackingEvents>
            <VideoClicks>
              <ClickThrough>https://cocacola.com/summer-promo</ClickThrough>
              <ClickTracking>https://adserver.com/track/click?id=12345</ClickTracking>
            </VideoClicks>
            <MediaFiles>
              <MediaFile id="media1" delivery="progressive" type="video/mp4" bitrate="1000" width="1920" height="1080">
                <![CDATA[https://cdn.adserver.com/ads/cocacola-30s.mp4]]>
              </MediaFile>
              <MediaFile id="media2" delivery="progressive" type="video/mp4" bitrate="500" width="1280" height="720">
                <![CDATA[https://cdn.adserver.com/ads/cocacola-30s-720p.mp4]]>
              </MediaFile>
            </MediaFiles>
          </Linear>
        </Creative>
      </Creatives>
    </InLine>
  </Ad>
</VAST>
```

### Elemen Penting:

1. **`<Ad>`**: Container untuk satu iklan
2. **`<InLine>`**: Ad content langsung di XML (vs `<Wrapper>` untuk redirect)
3. **`<Impression>`**: URL untuk track impression (dipanggil saat iklan mulai)
4. **`<Duration>`**: Durasi iklan (format: HH:MM:SS)
5. **`<TrackingEvents>`**: URLs untuk track events (start, quartile, complete)
6. **`<VideoClicks>`**: 
   - `<ClickThrough>`: URL untuk redirect saat user click iklan
   - `<ClickTracking>`: URL untuk track click event
7. **`<MediaFiles>`**: URLs ke video files (bisa multiple untuk different bitrates)

## Bagaimana VAST Digunakan di FAST Ads Backend?

### 1. Storage di Database

Di sistem ini, VAST URL disimpan di tabel `ads`:

```sql
ads:
  - id
  - vast_url: "https://adserver.com/vast2.xml"
  - duration_seconds: 30
  - click_through_url: "https://cocacola.com/promo"
```

### 2. Ad Decision Flow

```
1. Golang SSAI Service detect ad break
   ↓
2. Call Laravel: POST /api/v1/ads/decision
   {
     tenant_id: 1,
     channel: "news",
     position: "mid-roll",
     duration_seconds: 120
   }
   ↓
3. Laravel Ad Decision Service:
   - Filter ads berdasarkan rules
   - Return list of ads dengan VAST URLs
   ↓
4. Response:
   {
     "ads": [
       {
         "ad_id": 1,
         "vast_url": "https://adserver.com/vast2.xml",
         "duration_seconds": 30,
         "click_through_url": "https://cocacola.com/promo"
       },
       {
         "ad_id": 2,
         "vast_url": "https://adserver.com/vast3.xml",
         "duration_seconds": 15,
         "click_through_url": "https://nike.com/sale"
       }
     ]
   }
   ↓
5. Golang SSAI Service:
   - Fetch VAST XML dari setiap VAST URL
   - Extract video URLs dari VAST
   - Stitch ads ke HLS manifest
   ↓
6. Player receive manifest dengan ads
```

### 3. VAST Processing di Golang SSAI

```go
// Pseudo-code untuk VAST processing
func processVAST(vastURL string) (*AdContent, error) {
    // 1. Fetch VAST XML
    resp, err := http.Get(vastURL)
    vastXML := parseVAST(resp.Body)
    
    // 2. Extract video URLs
    videoURLs := vastXML.MediaFiles
    
    // 3. Extract tracking URLs
    trackingURLs := vastXML.TrackingEvents
    
    // 4. Extract click-through URL
    clickThrough := vastXML.ClickThrough
    
    return &AdContent{
        VideoURLs: videoURLs,
        TrackingURLs: trackingURLs,
        ClickThrough: clickThrough,
    }, nil
}
```

## VAST Wrapper (Redirect)

Ada 2 jenis VAST response:

### 1. InLine VAST (Direct)
```xml
<VAST>
  <Ad>
    <InLine>
      <!-- Ad content langsung di sini -->
    </InLine>
  </Ad>
</VAST>
```

### 2. Wrapper VAST (Redirect)
```xml
<VAST>
  <Ad>
    <Wrapper>
      <AdSystem>Ad Network</AdSystem>
      <VASTAdTagURI>
        <![CDATA[https://another-adserver.com/vast.xml?params=...]]>
      </VASTAdTagURI>
      <Impression>https://wrapper.com/track/impression</Impression>
    </Wrapper>
  </Ad>
</VAST>
```

**Wrapper** digunakan untuk:
- Ad networks yang redirect ke ad server lain
- Programmatic ad buying (RTB)
- Ad exchanges

Player harus follow redirect sampai mendapatkan InLine VAST.

## Tracking Events

VAST mendefinisikan tracking events berikut:

| Event | Description | When Fired |
|-------|-------------|------------|
| `impression` | Ad mulai dimuat | Saat ad break dimulai |
| `start` | Ad mulai play | Saat video ad mulai play |
| `firstQuartile` | 25% ad selesai | Saat 25% ad sudah diputar |
| `midpoint` | 50% ad selesai | Saat 50% ad sudah diputar |
| `thirdQuartile` | 75% ad selesai | Saat 75% ad sudah diputar |
| `complete` | Ad selesai | Saat 100% ad sudah diputar |
| `click` | User click iklan | Saat user click iklan |

### Contoh Tracking Flow:

```
1. Player load ad → Call impression URL
   GET https://adserver.com/track/impression?id=12345
   
2. Player start playing → Call start URL
   GET https://adserver.com/track/start?id=12345
   
3. Player reach 25% → Call firstQuartile URL
   GET https://adserver.com/track/firstQuartile?id=12345
   
4. Player reach 50% → Call midpoint URL
   GET https://adserver.com/track/midpoint?id=12345
   
5. Player reach 75% → Call thirdQuartile URL
   GET https://adserver.com/track/thirdQuartile?id=12345
   
6. Player finish ad → Call complete URL
   GET https://adserver.com/track/complete?id=12345
```

## VAST di FAST Ads Backend

### Database Schema

```sql
ads:
  - vast_url: VARCHAR(512)  -- URL ke VAST XML
  - duration_seconds: INT    -- Durasi iklan
  - click_through_url: VARCHAR(512)  -- Optional, bisa juga dari VAST
```

### Ad Decision Response

Laravel API return VAST URLs dalam response:

```json
{
  "success": true,
  "data": {
    "ads": [
      {
        "ad_id": 1,
        "vast_url": "https://adserver.com/vast2.xml",
        "duration_seconds": 30,
        "ad_type": "linear",
        "click_through_url": "https://cocacola.com/promo",
        "tracking_urls": {
          "impression": "https://api-ads.wkkworld.com/api/v1/tracking/events?ad_id=1&event_type=impression",
          "start": "https://api-ads.wkkworld.com/api/v1/tracking/events?ad_id=1&event_type=start",
          "complete": "https://api-ads.wkkworld.com/api/v1/tracking/events?ad_id=1&event_type=complete"
        }
      }
    ],
    "total_duration_seconds": 30
  }
}
```

### Golang SSAI Processing

Golang service akan:
1. **Fetch VAST XML** dari `vast_url`
2. **Parse VAST** untuk extract:
   - Video URLs (MediaFiles)
   - Tracking URLs
   - Click-through URL
3. **Stitch ads** ke HLS manifest
4. **Emit tracking events** ke Laravel tracking endpoint

## Contoh Use Case

### Scenario: Coca Cola Ad

**1. Admin Create Ad di Dashboard:**
```
Name: Coca Cola 30s
VAST URL: https://adserver.com/vast2.xml
Duration: 30 seconds
Campaign: Summer Sale 2024
```

**2. Ad Break Terjadi:**
```
Player request: GET /fast/wkkworld/news.m3u8
Golang detect ad break di 6 minutes
```

**3. Golang Call Laravel:**
```http
POST /api/v1/ads/decision
{
  "tenant_id": 1,
  "channel": "news",
  "position": "mid-roll",
  "duration_seconds": 120
}
```

**4. Laravel Response:**
```json
{
  "ads": [
    {
      "ad_id": 1,
      "vast_url": "https://adserver.com/vast2.xml",
      "duration_seconds": 30
    }
  ]
}
```

**5. Golang Process VAST:**
```go
// Fetch VAST XML
vastXML := fetchVAST("https://adserver.com/vast2.xml")

// Extract video URL
videoURL := vastXML.MediaFiles[0].URL
// Result: https://cdn.adserver.com/ads/cocacola-30s.mp4

// Extract tracking URLs
impressionURL := vastXML.TrackingEvents["impression"]
// Result: https://adserver.com/track/impression?id=12345
```

**6. Golang Stitch ke Manifest:**
```m3u8
#EXTM3U
#EXT-X-VERSION:3
#EXT-X-TARGETDURATION:10

# Content segment 1
#EXTINF:10.0,
segment1.ts

# Content segment 2
#EXTINF:10.0,
segment2.ts

# Ad Break Start
#EXT-X-DISCONTINUITY
#EXTINF:30.0,
https://cdn.adserver.com/ads/cocacola-30s.mp4

# Content segment 3
#EXT-X-DISCONTINUITY
#EXTINF:10.0,
segment3.ts
```

**7. Player Play:**
- Play content → Ad break → Play ad → Continue content
- Emit tracking events ke Laravel

## Best Practices

### 1. VAST URL Management
- ✅ Store VAST URLs di database
- ✅ Validate VAST URLs sebelum activate ads
- ✅ Support VAST 2.0, 3.0, dan 4.0
- ✅ Handle VAST wrapper redirects

### 2. VAST Processing
- ✅ Cache VAST XML responses (60-300 seconds)
- ✅ Handle VAST errors gracefully
- ✅ Support multiple MediaFiles (different bitrates)
- ✅ Extract all tracking URLs

### 3. Tracking
- ✅ Emit impression saat ad break dimulai
- ✅ Emit quartile events (25%, 50%, 75%)
- ✅ Emit complete saat ad selesai
- ✅ Track clicks untuk analytics

### 4. Error Handling
- ✅ Handle VAST fetch failures
- ✅ Handle invalid VAST XML
- ✅ Fallback ke default ads jika VAST gagal
- ✅ Log errors untuk debugging

## Summary

| Aspect | Description |
|--------|-------------|
| **VAST** | Standar XML untuk ad serving |
| **VAST URL** | URL ke file XML yang berisi info iklan |
| **Format** | XML dengan struktur `<VAST><Ad><InLine>...</InLine></Ad></VAST>` |
| **Purpose** | Komunikasi antara player dan ad server |
| **Tracking** | Impression, quartile, complete, click events |
| **In FAST Ads** | Stored in `ads.vast_url`, processed by Golang SSAI |

VAST adalah standar industry untuk video ad serving, dan sistem FAST Ads Backend menggunakan VAST untuk:
- Store ad metadata (VAST URLs)
- Process ads di SSAI service
- Track ad performance
- Support multiple ad servers

