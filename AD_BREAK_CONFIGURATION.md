# Konfigurasi Ad Break - Panduan Lengkap

## Overview
Sistem FAST Ads Backend mendukung 3 jenis ad breaks:
1. **Pre-roll**: Ad muncul saat subscriber pertama buka channel
2. **Mid-roll**: Ad muncul setiap X menit selama streaming
3. **Post-roll**: Ad muncul di akhir content

## 1. Pre-Roll Ads (Saat Pertama Buka Channel)

### Apa itu Pre-Roll?
Pre-roll adalah iklan yang muncul **sebelum** content dimulai, tepat saat subscriber pertama kali membuka channel.

### Cara Setup Pre-Roll:

**Option 1: Via Channel Configuration**
1. Buka Channel → Edit
2. Set **Ad Break Interval** = 0 (untuk disable auto mid-roll)
3. Buat Ad Break manual dengan position = "pre-roll"

**Option 2: Via Ad Breaks Management**
1. Buka Channel → Show
2. Klik "Manage Ad Breaks"
3. Create Ad Break:
   - **Position Type**: Pre-roll
   - **Offset Seconds**: 0 (auto-set)
   - **Duration Seconds**: 30-120 (durasi ad break)
   - **Status**: Active

### Contoh Pre-Roll Flow:
```
1. Subscriber buka channel "News"
   ↓
2. Player request: GET /fast/wkkworld/news.m3u8
   ↓
3. Golang SSAI detect: Pre-roll ad break
   ↓
4. Call Laravel: POST /api/v1/ads/decision
   {
     position: "pre-roll",
     duration_seconds: 60
   }
   ↓
5. Laravel return ads untuk pre-roll
   ↓
6. Golang stitch pre-roll ads ke manifest
   ↓
7. Player play: Ads → Content
```

## 2. Mid-Roll Ads (Per Berapa Menit)

### Apa itu Mid-Roll?
Mid-roll adalah iklan yang muncul **selama** streaming, setiap X menit.

### Cara Setup Mid-Roll:

**Option 1: Automatic (Interval-based)**
1. Buka Channel → Edit
2. Set **Ad Break Interval (seconds)**:
   - 180 = setiap 3 menit
   - 360 = setiap 6 menit (default)
   - 600 = setiap 10 menit
   - 900 = setiap 15 menit
3. Set **Ad Break Strategy**: "Static"
4. Save

**Option 2: Manual (Specific Times)**
1. Buka Channel → Show → Manage Ad Breaks
2. Create Ad Break:
   - **Position Type**: Mid-roll
   - **Offset Seconds**: 180 (3 menit dari start)
   - **Duration Seconds**: 60
   - **Status**: Active
3. Create multiple ad breaks untuk different times

### Contoh Mid-Roll Flow:
```
1. Channel config: ad_break_interval_seconds = 360 (6 menit)
   ↓
2. Player streaming content
   ↓
3. Setelah 6 menit, Golang detect ad break
   ↓
4. Call Laravel untuk ads
   ↓
5. Stitch ads ke manifest
   ↓
6. Player play: Content → Ads → Content (lanjut)
```

### Konfigurasi Interval:

| Interval (seconds) | Menit | Keterangan |
|-------------------|-------|------------|
| 180 | 3 menit | Very frequent (aggressive) |
| 360 | 6 menit | Default (balanced) |
| 600 | 10 menit | Moderate |
| 900 | 15 menit | Less frequent |
| 1200 | 20 menit | Minimal ads |

## 3. Post-Roll Ads (Di Akhir Content)

### Apa itu Post-Roll?
Post-roll adalah iklan yang muncul **setelah** content selesai.

### Cara Setup Post-Roll:
1. Buka Channel → Show → Manage Ad Breaks
2. Create Ad Break:
   - **Position Type**: Post-roll
   - **Offset Seconds**: 0 (auto-set)
   - **Duration Seconds**: 30-120
   - **Status**: Active

## Konfigurasi Lengkap

### Di Channel Form:

**Ad Break Strategy:**
- **Static**: Berdasarkan interval waktu (default)
- **SCTE-35**: Dari tags di HLS manifest
- **Hybrid**: Kombinasi SCTE-35 + static fallback

**Ad Break Interval:**
- Input dalam **seconds**
- Contoh: 360 = 6 menit
- 0 = disable automatic mid-roll (hanya manual ad breaks)

### Di Ad Breaks Management:

**Position Type:**
- **pre-roll**: Saat pertama buka channel
- **mid-roll**: Selama streaming (setiap X menit atau specific time)
- **post-roll**: Di akhir content

**Offset Seconds:**
- Untuk **pre-roll** dan **post-roll**: Auto-set ke 0
- Untuk **mid-roll**: Detik dari start (contoh: 180 = 3 menit)

**Duration Seconds:**
- Durasi total ad break (30-300 seconds)
- Sistem akan fill dengan multiple ads jika perlu

**Priority:**
- Higher priority = ad break ini diprioritaskan
- 0-100, default: 0

## Contoh Use Cases

### Use Case 1: Pre-Roll + Mid-Roll Setiap 6 Menit

**Channel Configuration:**
```
Ad Break Strategy: Static
Ad Break Interval: 360 (6 menit)
```

**Ad Breaks:**
1. Pre-roll: Position = pre-roll, Duration = 60s
2. Mid-roll: Auto-generated setiap 6 menit

**Result:**
- Subscriber buka channel → Pre-roll ad (60s)
- Setelah 6 menit → Mid-roll ad
- Setelah 12 menit → Mid-roll ad
- Dan seterusnya...

### Use Case 2: Pre-Roll + Specific Mid-Roll Times

**Channel Configuration:**
```
Ad Break Interval: 0 (disable auto)
```

**Ad Breaks:**
1. Pre-roll: Position = pre-roll, Duration = 30s
2. Mid-roll 1: Position = mid-roll, Offset = 180 (3 menit), Duration = 60s
3. Mid-roll 2: Position = mid-roll, Offset = 600 (10 menit), Duration = 90s
4. Post-roll: Position = post-roll, Duration = 30s

**Result:**
- Saat buka → Pre-roll (30s)
- Di 3 menit → Mid-roll (60s)
- Di 10 menit → Mid-roll (90s)
- Di akhir → Post-roll (30s)

### Use Case 3: Hanya Pre-Roll (No Mid-Roll)

**Channel Configuration:**
```
Ad Break Interval: 0
```

**Ad Breaks:**
1. Pre-roll: Position = pre-roll, Duration = 60s

**Result:**
- Saat buka → Pre-roll (60s)
- Tidak ada mid-roll ads
- Content streaming tanpa interruption

## Best Practices

### 1. Pre-Roll
- ✅ Durasi: 30-60 seconds (jangan terlalu panjang)
- ✅ Gunakan untuk welcome message atau sponsor utama
- ✅ Jangan terlalu banyak ads (1-2 ads cukup)

### 2. Mid-Roll
- ✅ Interval: 6-10 menit (balance antara revenue dan UX)
- ✅ Durasi: 60-120 seconds per break
- ✅ Jangan terlalu frequent (bisa frustrate users)

### 3. Post-Roll
- ✅ Durasi: 30-60 seconds
- ✅ Gunakan untuk call-to-action atau next content promo

### 4. Total Ad Load
- ✅ Pre-roll: 1 break
- ✅ Mid-roll: 3-5 breaks per hour (jika content 1 jam)
- ✅ Post-roll: 1 break
- ✅ Total ad time: 10-15% dari total content time

## Technical Details

### Ad Break Detection Flow:

```
1. Player request manifest: GET /fast/{tenant}/{channel}.m3u8
   ↓
2. Golang SSAI Service:
   a. Fetch channel config dari Laravel
   b. Check ad_break_strategy:
      - Static: Use ad_break_interval_seconds
      - SCTE-35: Parse manifest tags
      - Hybrid: Try SCTE-35, fallback to static
   c. Check manual ad breaks (pre-roll, specific mid-roll, post-roll)
   ↓
3. Detect ad breaks:
   - Pre-roll: Always at start
   - Mid-roll: Based on interval or manual breaks
   - Post-roll: At end of content
   ↓
4. For each ad break:
   - Call Laravel: POST /api/v1/ads/decision
   - Get ads based on position, duration, rules
   - Stitch ads ke manifest
   ↓
5. Return stitched manifest ke player
```

### Database Schema:

**channels:**
- `ad_break_strategy`: static/scte35/hybrid
- `ad_break_interval_seconds`: Interval untuk auto mid-roll

**ad_breaks:**
- `channel_id`: FK ke channels
- `position_type`: pre-roll/mid-roll/post-roll
- `offset_seconds`: Detik dari start (untuk mid-roll)
- `duration_seconds`: Durasi ad break
- `priority`: Prioritas ad break
- `status`: active/inactive

## Summary

| Feature | Configuration | Location |
|---------|--------------|----------|
| **Pre-roll** | Create Ad Break dengan position = pre-roll | Channel → Manage Ad Breaks |
| **Mid-roll (Auto)** | Set Ad Break Interval di Channel | Channel → Edit |
| **Mid-roll (Manual)** | Create Ad Break dengan position = mid-roll, set offset | Channel → Manage Ad Breaks |
| **Post-roll** | Create Ad Break dengan position = post-roll | Channel → Manage Ad Breaks |

Semua konfigurasi bisa dilakukan via dashboard tanpa perlu coding!

