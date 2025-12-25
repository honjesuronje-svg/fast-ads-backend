# Panduan: Mendapatkan HLS URL dari VAST

## Overview

VAST XML biasanya berisi **video file URL** (MP4, progressive), bukan **HLS manifest URL** (.m3u8). Untuk SSAI, kita perlu HLS segments untuk stitch ke manifest.

## Struktur VAST - Dimana Video URL?

### Lokasi Video URL di VAST XML:

```xml
<VAST version="3.0">
  <Ad>
    <InLine>
      <Creatives>
        <Creative>
          <Linear>
            <MediaFiles>
              <MediaFile type="video/mp4" delivery="progressive">
                https://cdn.adserver.com/ads/cocacola-30s.mp4  <!-- INI URL VIDEO -->
              </MediaFile>
            </MediaFiles>
          </Linear>
        </Creative>
      </Creatives>
    </InLine>
  </Ad>
</VAST>
```

**Video URL ada di:** `<MediaFile>` tag, bukan di root VAST.

## Perbedaan: Video File vs HLS Manifest

| Type | Format | URL Example | Usage |
|------|--------|-------------|-------|
| **Video File** | MP4, WebM | `https://cdn.com/video.mp4` | Progressive download, direct play |
| **HLS Manifest** | M3U8 | `https://cdn.com/video.m3u8` | Streaming dengan segments (.ts files) |

### Untuk SSAI, kita perlu:
- **HLS Manifest** (.m3u8) untuk stitch ke content manifest
- **HLS Segments** (.ts files) untuk actual video chunks

## Skenario di Sistem Ini

### Skenario 1: Uploaded Video (Auto-generate VAST)

**Flow:**
```
1. Admin upload video: cocacola-30s.mp4
   ↓
2. Sistem generate VAST XML dengan video URL:
   <MediaFile>https://ads.wkkworld.com/storage/ads/videos/cocacola-30s.mp4</MediaFile>
   ↓
3. VAST URL: https://ads.wkkworld.com/storage/ads/vast/vast_123.xml
   ↓
4. Golang SSAI fetch VAST → Extract video URL
   ↓
5. PROBLEM: Video adalah MP4, bukan HLS!
```

**Solusi yang Diperlukan:**
- **Option A**: Convert video ke HLS saat upload
- **Option B**: Serve MP4 sebagai single segment (tidak ideal untuk SSAI)
- **Option C**: Use external HLS transcoding service

### Skenario 2: External VAST (Google Ads, AdX, dll)

**Flow:**
```
1. Admin input VAST URL: https://googleads.g.doubleclick.net/...
   ↓
2. Golang SSAI fetch VAST XML
   ↓
3. Parse VAST → Extract MediaFile URLs
   ↓
4. MediaFile URLs biasanya MP4, bukan HLS
   ↓
5. PROBLEM: Perlu convert atau handle MP4
```

## Cara Extract Video URL dari VAST

### Di Golang SSAI Service:

```go
// Pseudo-code untuk parse VAST
func extractVideoURLsFromVAST(vastXML string) ([]string, error) {
    // Parse VAST XML
    doc, err := xml.Parse(strings.NewReader(vastXML))
    
    // Find MediaFiles
    mediaFiles := doc.FindAll("//MediaFile")
    
    var videoURLs []string
    for _, mediaFile := range mediaFiles {
        url := mediaFile.GetText() // Get CDATA content
        videoURLs = append(videoURLs, url)
    }
    
    return videoURLs, nil
}
```

### Contoh VAST Parsing:

```go
package parser

import (
    "encoding/xml"
    "strings"
)

type VAST struct {
    XMLName xml.Name `xml:"VAST"`
    Ad      Ad       `xml:"Ad"`
}

type Ad struct {
    InLine InLine `xml:"InLine"`
}

type InLine struct {
    Creatives Creatives `xml:"Creatives"`
}

type Creatives struct {
    Creative Creative `xml:"Creative"`
}

type Creative struct {
    Linear Linear `xml:"Linear"`
}

type Linear struct {
    MediaFiles MediaFiles `xml:"MediaFiles"`
}

type MediaFiles struct {
    MediaFile []MediaFile `xml:"MediaFile"`
}

type MediaFile struct {
    Type    string `xml:"type,attr"`
    Delivery string `xml:"delivery,attr"`
    URL     string `xml:",chardata"`
}

func ParseVAST(vastXML string) ([]string, error) {
    var vast VAST
    err := xml.Unmarshal([]byte(vastXML), &vast)
    if err != nil {
        return nil, err
    }
    
    var urls []string
    for _, mediaFile := range vast.Ad.InLine.Creatives.Creative.Linear.MediaFiles.MediaFile {
        urls = append(urls, strings.TrimSpace(mediaFile.URL))
    }
    
    return urls, nil
}
```

## Solusi: Convert Video ke HLS

### Option 1: Transcode saat Upload (Recommended)

**Flow:**
```
1. Admin upload video: cocacola-30s.mp4
   ↓
2. Laravel trigger transcoding job:
   - Convert MP4 → HLS (.m3u8 + .ts segments)
   - Store di: storage/app/public/ads/hls/{ad_id}/
   ↓
3. Generate VAST dengan HLS URL:
   <MediaFile type="application/x-mpegURL" delivery="streaming">
     https://ads.wkkworld.com/storage/ads/hls/123/video.m3u8
   </MediaFile>
   ↓
4. Golang SSAI fetch VAST → Get HLS manifest URL
   ↓
5. Parse HLS manifest → Get segments
   ↓
6. Stitch segments ke content manifest
```

**Tools untuk Transcoding:**
- **FFmpeg**: Open source, powerful
- **AWS MediaConvert**: Cloud service
- **GStreamer**: Alternative to FFmpeg

### Option 2: On-the-fly Conversion (Not Recommended)

Convert MP4 ke HLS saat ad break terjadi (too slow untuk real-time).

### Option 3: Serve MP4 as Single Segment

**Workaround:**
```m3u8
#EXTM3U
#EXT-X-VERSION:3
#EXT-X-TARGETDURATION:30
#EXTINF:30.0,
https://cdn.com/video.mp4
#EXT-X-ENDLIST
```

Ini bukan true HLS, tapi bisa work untuk simple cases.

## Implementasi di Sistem Ini

### Current State:

**Uploaded Video:**
- Video disimpan sebagai MP4
- VAST generated dengan MP4 URL
- **Issue**: Golang perlu handle MP4, bukan HLS

**External VAST:**
- Golang fetch VAST
- Extract MediaFile URLs
- **Issue**: URLs biasanya MP4, perlu convert atau handle

### Recommended Solution:

**1. Add HLS Transcoding saat Upload:**

```php
// In AdController@store
if ($validated['ad_source'] === 'uploaded_video') {
    // Upload video
    $videoPath = $videoFile->store('ads/videos', 'public');
    
    // Trigger transcoding job
    TranscodeVideoToHLS::dispatch($ad, $videoPath);
    
    // Generate VAST dengan HLS URL (after transcoding)
    $hlsManifestUrl = Storage::disk('public')->url("ads/hls/{$ad->id}/video.m3u8");
    $vastXml = $this->vastGenerator->generateVAST($ad, $hlsManifestUrl);
}
```

**2. Update VAST Generator untuk HLS:**

```php
// In VASTGeneratorService
$vast .= '            <MediaFiles>' . "\n";
$vast .= '              <MediaFile id="media_' . $ad->id . '" delivery="streaming" type="application/x-mpegURL">' . "\n";
$vast .= '                <![CDATA[' . htmlspecialchars($hlsManifestUrl) . ']]>' . "\n";
$vast .= '              </MediaFile>' . "\n";
$vast .= '            </MediaFiles>' . "\n";
```

**3. Golang Parse VAST untuk HLS:**

```go
// In golang-ssai/internal/parser/vast.go
func ExtractHLSManifestFromVAST(vastXML string) (string, error) {
    // Parse VAST
    // Find MediaFile with type="application/x-mpegURL"
    // Return HLS manifest URL
}
```

## Best Practices

### 1. Video Format untuk Ads:
- ✅ **HLS** (.m3u8): Best for SSAI, streaming
- ✅ **DASH** (.mpd): Alternative to HLS
- ⚠️ **MP4** (progressive): Works but not ideal for SSAI

### 2. VAST MediaFile Types:
```xml
<!-- HLS -->
<MediaFile type="application/x-mpegURL" delivery="streaming">
  https://cdn.com/video.m3u8
</MediaFile>

<!-- MP4 Progressive -->
<MediaFile type="video/mp4" delivery="progressive">
  https://cdn.com/video.mp4
</MediaFile>
```

### 3. Transcoding Requirements:
- **Resolution**: 1920x1080, 1280x720 (multiple bitrates)
- **Segment Duration**: 6-10 seconds
- **Codec**: H.264 video, AAC audio
- **Format**: HLS with .ts segments

## Summary

### Dimana Video URL di VAST?
**Lokasi:** `<MediaFile>` tag di dalam `<MediaFiles>` → `<Linear>` → `<Creative>` → `<Creatives>` → `<InLine>` → `<Ad>` → `<VAST>`

### Bagaimana Mendapatkan HLS?
1. **Parse VAST XML** → Extract MediaFile URLs
2. **Check MediaFile type**:
   - `application/x-mpegURL` = HLS (langsung bisa digunakan)
   - `video/mp4` = Perlu convert ke HLS
3. **Jika MP4**: Transcode ke HLS (FFmpeg) atau serve as single segment

### Untuk Sistem Ini:
- **Current**: VAST berisi MP4 URLs
- **Recommended**: Add HLS transcoding saat upload
- **Golang**: Parse VAST, extract URLs, handle MP4 atau HLS

## Next Steps

1. ✅ Understand VAST structure
2. ⏳ Add HLS transcoding untuk uploaded videos
3. ⏳ Update VAST generator untuk HLS URLs
4. ⏳ Update Golang parser untuk handle both MP4 dan HLS
5. ⏳ Test dengan external VAST (Google Ads, etc.)

