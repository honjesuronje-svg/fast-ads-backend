# Video Upload & Auto-Generate VAST Feature

## Overview
Fitur ini memungkinkan admin untuk:
1. **Upload video iklan statis** dan otomatis generate VAST XML
2. **Input VAST URL** dari external ad server (Google Ads, AdX, dll)

## Fitur yang Ditambahkan

### 1. Database Migration
- Field `video_file_path`: Path ke video file yang di-upload
- Field `ad_source`: Enum ('vast_url', 'uploaded_video')

### 2. VAST Generator Service
- **File**: `app/Services/VASTGeneratorService.php`
- **Fungsi**:
  - Generate VAST XML 3.0 dari video yang di-upload
  - Include tracking URLs (impression, quartile, complete)
  - Include click-through URL
  - Save VAST XML ke storage dan return URL

### 3. Updated Ad Controller
- Support 2 mode:
  - **VAST URL Mode**: Input VAST URL dari external ad server
  - **Upload Video Mode**: Upload video file, auto-generate VAST

### 4. Updated Create Form
- Dropdown untuk pilih "Ad Source"
- Conditional fields berdasarkan pilihan
- JavaScript untuk toggle fields

## Cara Penggunaan

### Mode 1: Upload Video (Auto-generate VAST)

1. Buka: https://ads.wkkworld.com/ads/create
2. Pilih "Ad Source": **Upload Video (Auto-generate VAST)**
3. Upload video file (MP4, WebM, AVI, MOV, max 100MB)
4. Isi field lainnya:
   - Tenant
   - Campaign (optional)
   - Name
   - Duration (seconds)
   - Ad Type
   - Click Through URL (optional)
   - Status
5. Submit form
6. Sistem akan:
   - Upload video ke `storage/app/public/ads/videos/`
   - Generate VAST XML dengan tracking URLs
   - Save VAST XML ke `storage/app/public/ads/vast/`
   - Store VAST URL di database

### Mode 2: VAST URL (External Ad Server)

1. Buka: https://ads.wkkworld.com/ads/create
2. Pilih "Ad Source": **VAST URL (External Ad Server)**
3. Input VAST URL, contoh:
   - Google Ads: `https://googleads.g.doubleclick.net/pagead/ads?...`
   - AdX: `https://pubads.g.doubleclick.net/gampad/ads?...`
   - Custom ad server: `https://adserver.com/vast2.xml`
4. Isi field lainnya
5. Submit form

## Struktur VAST yang Di-generate

```xml
<?xml version="1.0" encoding="UTF-8"?>
<VAST version="3.0">
  <Ad id="123">
    <InLine>
      <AdSystem>FAST Ads Backend</AdSystem>
      <AdTitle>Coca Cola Commercial</AdTitle>
      <Impression>https://ads.wkkworld.com/api/v1/tracking/events?ad_id=123&event_type=impression</Impression>
      <Creatives>
        <Creative>
          <Linear>
            <Duration>00:00:30</Duration>
            <TrackingEvents>
              <Tracking event="start">...</Tracking>
              <Tracking event="firstQuartile">...</Tracking>
              <Tracking event="midpoint">...</Tracking>
              <Tracking event="thirdQuartile">...</Tracking>
              <Tracking event="complete">...</Tracking>
            </TrackingEvents>
            <VideoClicks>
              <ClickThrough>https://cocacola.com/promo</ClickThrough>
              <ClickTracking>...</ClickTracking>
            </VideoClicks>
            <MediaFiles>
              <MediaFile type="video/mp4">
                https://ads.wkkworld.com/storage/ads/videos/video_123.mp4
              </MediaFile>
            </MediaFiles>
          </Linear>
        </Creative>
      </Creatives>
    </InLine>
  </Ad>
</VAST>
```

## File Storage

### Video Files
- **Path**: `storage/app/public/ads/videos/`
- **Public URL**: `https://ads.wkkworld.com/storage/ads/videos/{filename}`
- **Format**: MP4, WebM, AVI, MOV
- **Max Size**: 100MB

### VAST XML Files
- **Path**: `storage/app/public/ads/vast/`
- **Public URL**: `https://ads.wkkworld.com/storage/ads/vast/vast_{ad_id}_{timestamp}.xml`
- **Format**: XML

## Tracking URLs

Semua tracking URLs otomatis di-generate dengan format:
```
https://ads.wkkworld.com/api/v1/tracking/events?ad_id={ad_id}&event_type={event_type}
```

Events:
- `impression`: Saat ad break dimulai
- `start`: Saat video ad mulai play
- `first_quartile`: 25% ad sudah diputar
- `midpoint`: 50% ad sudah diputar
- `third_quartile`: 75% ad sudah diputar
- `complete`: 100% ad sudah diputar
- `click`: User click iklan

## Contoh Use Cases

### Use Case 1: Upload Video Lokal
```
1. Admin upload video: "cocacola-30s.mp4"
2. Sistem generate VAST XML
3. VAST URL: https://ads.wkkworld.com/storage/ads/vast/vast_123_1234567890.xml
4. Video URL: https://ads.wkkworld.com/storage/ads/videos/cocacola-30s.mp4
5. Ad siap digunakan di ad breaks
```

### Use Case 2: Google Ads Integration
```
1. Admin input VAST URL dari Google Ads
2. VAST URL: https://googleads.g.doubleclick.net/pagead/ads?...
3. Sistem store VAST URL
4. Ketika ad break terjadi, Golang fetch VAST dari Google
5. Google return ads berdasarkan targeting
```

## Best Practices

1. **Video Upload**:
   - Gunakan format MP4 untuk kompatibilitas terbaik
   - Optimize video size (compression)
   - Max 100MB per file
   - Recommended resolution: 1920x1080 atau 1280x720

2. **VAST URL**:
   - Test VAST URL sebelum save
   - Pastikan VAST URL accessible
   - Support VAST wrapper redirects

3. **Storage**:
   - Monitor storage usage
   - Setup CDN untuk video files (optional)
   - Regular cleanup old files

## Technical Details

### Controller Logic
```php
if (ad_source === 'uploaded_video') {
    // Upload video
    // Generate VAST XML
    // Save VAST XML
    // Store paths
} else {
    // Use provided VAST URL
    // Store VAST URL
}
```

### VAST Generation
- Version: VAST 3.0
- Includes all required tracking events
- Supports click-through URLs
- MediaFile dengan progressive delivery

### File Permissions
- Storage: `www-data:www-data` dengan 775 permissions
- Public link: `public/storage` → `storage/app/public`

## Next Steps

1. ✅ Video upload working
2. ✅ VAST auto-generation working
3. ✅ VAST URL input working
4. ⏳ Add video preview in edit form
5. ⏳ Add video duration auto-detection
6. ⏳ Add video transcoding (optional)
7. ⏳ Add CDN integration (optional)

