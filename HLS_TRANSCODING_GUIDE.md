# HLS Transcoding Guide - Auto Convert Video ke HLS Segments

## Overview

Sistem sekarang **otomatis convert video ke HLS format** saat upload. Video akan di-convert menjadi:
- **HLS Manifest** (.m3u8 file)
- **HLS Segments** (.ts files)

## Fitur yang Ditambahkan

### 1. FFmpeg Integration
- Auto-detect FFmpeg installation
- Convert video ke HLS dengan optimal settings

### 2. Transcode Command
- **Command**: `php artisan video:transcode-hls {ad_id} {video_path}`
- **Output**: HLS manifest + TS segments di `storage/app/public/ads/hls/{ad_id}/`

### 3. Auto-Transcode on Upload
- Saat upload video → Auto trigger transcoding
- Generate VAST dengan HLS URL (bukan MP4)
- Update ad dengan HLS manifest URL

## HLS Output Structure

```
storage/app/public/ads/hls/
  └── {ad_id}/
      ├── video.m3u8          # HLS manifest
      ├── segment_000.ts      # Segment 1
      ├── segment_001.ts      # Segment 2
      ├── segment_002.ts      # Segment 3
      └── ...
```

## FFmpeg Settings

### Current Configuration:
- **Codec**: H.264 (libx264) video, AAC audio
- **Segment Duration**: 6 seconds per segment
- **Bitrate**: 2000k video, 128k audio
- **Preset**: medium (balance antara speed dan quality)
- **Format**: HLS with TS segments

### FFmpeg Command:
```bash
ffmpeg -i input.mp4 \
  -c:v libx264 -c:a aac \
  -hls_time 6 \
  -hls_list_size 0 \
  -hls_segment_filename segment_%03d.ts \
  -hls_flags delete_segments \
  -start_number 0 \
  -b:v 2000k -b:a 128k \
  -preset medium \
  -f hls video.m3u8
```

## Flow Lengkap

### 1. Upload Video:
```
Admin upload: cocacola-30s.mp4
  ↓
Laravel save: storage/app/public/ads/videos/cocacola-30s.mp4
  ↓
Create Ad record (vast_url = 'temp')
  ↓
Trigger: php artisan video:transcode-hls {ad_id} {video_path}
```

### 2. Transcoding Process:
```
FFmpeg convert:
  Input:  storage/app/public/ads/videos/cocacola-30s.mp4
  Output: storage/app/public/ads/hls/{ad_id}/video.m3u8
          storage/app/public/ads/hls/{ad_id}/segment_000.ts
          storage/app/public/ads/hls/{ad_id}/segment_001.ts
          ...
  ↓
Generate VAST XML dengan HLS URL:
  <MediaFile type="application/x-mpegURL">
    https://ads.wkkworld.com/storage/ads/hls/{ad_id}/video.m3u8
  </MediaFile>
  ↓
Update Ad: vast_url = {generated_vast_url}
```

### 3. Golang SSAI Usage:
```
Golang fetch VAST:
  GET https://ads.wkkworld.com/storage/ads/vast/vast_{ad_id}.xml
  ↓
Parse VAST → Extract HLS URL:
  https://ads.wkkworld.com/storage/ads/hls/{ad_id}/video.m3u8
  ↓
Fetch HLS manifest:
  GET https://ads.wkkworld.com/storage/ads/hls/{ad_id}/video.m3u8
  ↓
Parse manifest → Get segments:
  segment_000.ts, segment_001.ts, ...
  ↓
Stitch segments ke content manifest
```

## HLS Manifest Example

### Generated HLS Manifest:
```m3u8
#EXTM3U
#EXT-X-VERSION:3
#EXT-X-TARGETDURATION:6
#EXT-X-MEDIA-SEQUENCE:0
#EXTINF:6.0,
segment_000.ts
#EXTINF:6.0,
segment_001.ts
#EXTINF:6.0,
segment_002.ts
#EXTINF:6.0,
segment_003.ts
#EXTINF:2.0,
segment_004.ts
#EXT-X-ENDLIST
```

## VAST XML dengan HLS

### Generated VAST:
```xml
<VAST version="3.0">
  <Ad id="123">
    <InLine>
      <MediaFiles>
        <MediaFile type="application/x-mpegURL" delivery="streaming">
          https://ads.wkkworld.com/storage/ads/hls/123/video.m3u8
        </MediaFile>
      </MediaFiles>
    </InLine>
  </Ad>
</VAST>
```

## Error Handling

### Jika FFmpeg tidak terinstall:
- System akan show error message
- Fallback ke MP4 format (dengan warning)
- Ad tetap dibuat dengan MP4 URL

### Jika Transcoding gagal:
- Log error ke Laravel logs
- Fallback ke MP4 format
- Admin akan dapat warning message

## Manual Transcoding

Jika perlu re-transcode video yang sudah ada:

```bash
cd /var/www/fast-ads-backend/laravel-backend
sudo -u www-data php artisan video:transcode-hls {ad_id} {video_path}
```

Contoh:
```bash
sudo -u www-data php artisan video:transcode-hls 1 ads/videos/cocacola-30s.mp4
```

## Storage Management

### HLS Files Location:
- **Manifest**: `storage/app/public/ads/hls/{ad_id}/video.m3u8`
- **Segments**: `storage/app/public/ads/hls/{ad_id}/segment_*.ts`
- **Public URL**: `https://ads.wkkworld.com/storage/ads/hls/{ad_id}/video.m3u8`

### Cleanup:
- Original MP4 tetap disimpan (untuk backup)
- HLS files bisa di-delete jika perlu space
- Re-transcode bisa dilakukan kapan saja

## Performance Considerations

### Transcoding Time:
- **30s video**: ~10-30 seconds (tergantung server)
- **60s video**: ~20-60 seconds
- **120s video**: ~40-120 seconds

### Recommendations:
- ✅ Transcode di background (queue job) untuk better UX
- ✅ Show progress indicator di dashboard
- ✅ Cache transcoded files
- ✅ Monitor storage usage

## Future Improvements

1. **Queue Jobs**: Transcode di background dengan Laravel Queue
2. **Progress Tracking**: Show transcoding progress di dashboard
3. **Multiple Bitrates**: Generate multiple quality levels (1080p, 720p, 480p)
4. **CDN Integration**: Upload HLS files ke CDN setelah transcoding
5. **Auto Cleanup**: Delete old segments automatically

## Testing

### Test Transcoding:
```bash
# 1. Upload video via dashboard
# 2. Check transcoding:
ls -la /var/www/fast-ads-backend/laravel-backend/storage/app/public/ads/hls/{ad_id}/

# 3. Test HLS manifest:
curl https://ads.wkkworld.com/storage/ads/hls/{ad_id}/video.m3u8

# 4. Test VAST:
curl https://ads.wkkworld.com/storage/ads/vast/vast_{ad_id}_*.xml
```

## Summary

✅ **Auto HLS Conversion**: Video otomatis di-convert ke HLS saat upload
✅ **FFmpeg Integration**: Menggunakan FFmpeg untuk transcoding
✅ **HLS Segments**: Generate .ts segments (6 seconds each)
✅ **VAST Update**: VAST XML otomatis menggunakan HLS URL
✅ **Error Handling**: Fallback ke MP4 jika transcoding gagal
✅ **Manual Re-transcode**: Bisa re-transcode kapan saja

Sekarang semua uploaded videos akan otomatis di-convert ke HLS format yang siap untuk SSAI!

