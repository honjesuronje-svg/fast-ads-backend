# Ad Break Detection Implementation

## Overview

Implementasi ad break detection untuk SSAI service yang mendukung:
- **SCTE-35 detection**: Deteksi cue points dari manifest HLS
- **Static rules**: Konfigurasi ad break dari Laravel (channel config)
- **Multiple ad breaks**: Pre-roll, mid-roll, dan post-roll

## Architecture

### Components

1. **AdBreakDetector Service** (`internal/service/adbreak_detector.go`)
   - Deteksi SCTE-35 cues dari manifest
   - Aplikasi static rules dari channel config
   - Deduplikasi dan sorting ad breaks

2. **M3U8 Parser** (`internal/parser/m3u8.go`)
   - Parsing manifest HLS
   - Stitching ads ke manifest
   - Support multiple ad breaks

3. **Manifest Handler** (`internal/handler/manifest.go`)
   - Integrasi ad break detection
   - Batch stitching untuk multiple breaks
   - Tracking events emission

## Ad Break Detection Flow

```
1. Fetch original manifest from origin CDN
2. Parse manifest to internal model
3. Get channel config from Laravel (static rules)
4. Detect ad breaks:
   - SCTE-35 cues (priority)
   - Static rules (fallback)
5. Get ads for each break from Laravel
6. Stitch all ads at once (batch processing)
7. Cache and return stitched manifest
```

## SCTE-35 Detection

Mendeteksi tag berikut dalam manifest:
- `#EXT-X-CUE-OUT`: Start of ad break
- `#EXT-X-CUE-IN`: End of ad break
- `#EXT-X-SCTE35`: SCTE-35 binary data

**Example:**
```
#EXTINF:10.0,
segment1.ts
#EXT-X-CUE-OUT:30
#EXTINF:10.0,
segment2.ts
#EXT-X-CUE-IN
#EXTINF:10.0,
segment3.ts
```

## Static Rules

Static rules dikonfigurasi di Laravel dan diambil via API:
- **Pre-roll**: Ads di awal stream (offset: 0)
- **Mid-roll**: Ads di tengah stream (offset: seconds from start)
- **Post-roll**: Ads di akhir stream (offset: seconds from end)
- **Interval**: Repeat interval untuk multiple mid-rolls

**Example Channel Config:**
```json
{
  "channel_id": 1,
  "ad_rules": [
    {
      "position": "pre-roll",
      "duration": 30
    },
    {
      "position": "mid-roll",
      "offset": 360,
      "duration": 120,
      "interval": 600
    },
    {
      "position": "post-roll",
      "offset": 30,
      "duration": 30
    }
  ]
}
```

## Ad Stitching

### Single Break Stitching
```go
stitchedManifest, err := parser.StitchAds(manifest, ads, breakPoint)
```

### Multiple Breaks Stitching (Recommended)
```go
adBreaksWithAds := []parser.AdBreakWithAds{
    {Offset: 0, Ads: preRollAds},
    {Offset: 360, Ads: midRollAds},
    {Offset: 720, Ads: midRollAds2},
}
stitchedManifest, err := parser.StitchMultipleAdBreaks(manifest, adBreaksWithAds)
```

### Stitching Process
1. Parse manifest ke HLS model
2. Sort ad breaks by offset (ascending)
3. Process breaks in reverse order (to avoid index shifting)
4. Find insertion point berdasarkan cumulative duration
5. Insert ad segments dengan discontinuity markers
6. Render kembali ke M3U8 format

## Configuration

### Laravel Client Config
```yaml
laravel:
  base_url: "http://localhost:8000/api/v1"
  api_key: "your_api_key"
  timeout: 5s
```

### Channel Config API
```
GET /api/v1/channels/{channel}/config
Headers:
  X-API-Key: {api_key}
  X-Tenant-ID: {tenant_id}
```

## Testing

### Test Ad Break Detection
```bash
# Test dengan manifest yang mengandung SCTE-35
curl http://localhost:8080/fast/tenant1/channel1.m3u8

# Test dengan static rules
# Set channel config di Laravel terlebih dahulu
```

### Test Ad Stitching
```bash
# Request manifest dengan ad breaks
curl -v http://localhost:8080/fast/tenant1/channel1.m3u8 \
  -H "CF-IPCountry: US" \
  -H "User-Agent: TestPlayer/1.0"
```

## Performance Considerations

1. **Caching**: Manifest yang sudah di-stitch di-cache untuk mengurangi processing
2. **Batch Stitching**: Multiple breaks di-stitch sekaligus untuk efisiensi
3. **Async Tracking**: Tracking events dikirim secara async (goroutine)
4. **Error Handling**: Jika stitching gagal, return original manifest

## Future Enhancements

- [ ] Full SCTE-35 binary parsing
- [ ] Dynamic ad break detection dari segment metadata
- [ ] Ad break prediction berdasarkan viewing patterns
- [ ] A/B testing untuk ad break positions
- [ ] Real-time ad break adjustment

