# Best Practices: Ad Break Interval Configuration

## Overview
Konfigurasi ad break interval perlu disesuaikan dengan tipe stream (LIVE vs VOD) dan karakteristik manifest HLS.

## 1. LIVE Stream (Real-time streaming)

### Karakteristik LIVE Stream:
- Manifest pendek: biasanya 3-4 segments (15-24 detik)
- Tidak ada akhir yang diketahui (infinite stream)
- Manifest diperbarui setiap beberapa detik oleh player
- Segments bergeser terus-menerus (sliding window)

### Best Practice untuk LIVE Stream:

#### Option A: Interval Kecil (Recommended)
- **Interval: 15-30 detik**
- Cocok untuk manifest yang berisi 3-4 segments (15-24 detik)
- Ad akan muncul dalam setiap manifest window
- Contoh: Interval 20 detik pada manifest 24 detik → ad muncul di ~20 detik

#### Option B: Interval Besar (Auto-adjust)
- **Interval: 60+ detik**
- Sistem akan otomatis menyesuaikan:
  - Jika interval > manifest duration → ad di-insert di tengah manifest (50-75% dari duration)
  - Contoh: Interval 60 detik pada manifest 24 detik → ad muncul di ~12-18 detik
- Player akan terus request manifest baru, sehingga ad berikutnya akan muncul sesuai interval

### Konfigurasi Recommended untuk LIVE:
```
Ad Break Interval: 20-30 seconds (optimal)
Ad Break Interval: 60+ seconds (akan auto-adjust ke tengah manifest)
Enable Pre-Roll: No (di-skip untuk ExoPlayer compatibility)
```

## 2. VOD Stream (Video on Demand)

### Karakteristik VOD Stream:
- Manifest panjang: bisa ratusan atau ribuan detik
- Ada akhir yang diketahui (#EXT-X-ENDLIST)
- Manifest statis atau di-cache

### Best Practice untuk VOD Stream:
- **Interval: 60-300 detik (1-5 menit)**
- Ad breaks akan muncul sesuai interval yang dikonfigurasi
- Tidak ada limit untuk jumlah ad breaks

### Konfigurasi Recommended untuk VOD:
```
Ad Break Interval: 60-180 seconds (1-3 menit)
Enable Pre-Roll: Yes (optional)
```

## 3. Cara Kerja Sistem

### LIVE Stream dengan Interval Besar:
1. Player request manifest → Sistem generate ad break di tengah manifest
2. Player memutar stream → Ad muncul di tengah (misalnya 12 detik dari 24 detik)
3. Player request manifest baru setelah beberapa detik
4. Sistem menghitung waktu kumulatif dan insert ad berikutnya sesuai interval
5. Proses berlanjut untuk setiap manifest request

### LIVE Stream dengan Interval Kecil:
1. Player request manifest → Sistem generate ad break sesuai interval
2. Ad muncul pada posisi yang sesuai (misalnya 20 detik)
3. Manifest berikutnya akan berisi ad break berikutnya (misalnya 40 detik)

## 4. Rekomendasi Konfigurasi

### Untuk LIVE Channel (seperti indosiar):
```php
'ad_break_interval_seconds' => 20,  // Optimal untuk manifest 24 detik
// atau
'ad_break_interval_seconds' => 60,  // Akan auto-adjust ke tengah manifest
'enable_pre_roll' => false,         // Di-skip untuk ExoPlayer compatibility
```

### Untuk VOD Content:
```php
'ad_break_interval_seconds' => 120, // 2 menit
'enable_pre_roll' => true,          // Optional
```

## 5. Testing

### Test LIVE Stream:
1. Set interval: 20-30 detik (optimal) atau 60+ detik (auto-adjust)
2. Play stream di ExoPlayer atau player lain
3. Monitor log: `tail -f /tmp/ssai-service.log | grep "LIVE stream"`
4. Verifikasi: Ad muncul setiap interval yang dikonfigurasi

### Test VOD Stream:
1. Set interval: 60-180 detik
2. Play stream
3. Verifikasi: Ad muncul setiap interval

## 6. Troubleshooting

### Ad tidak muncul:
- Cek log: `tail -f /tmp/ssai-service.log | grep "Skipping ad break"`
- Pastikan interval tidak terlalu besar untuk manifest duration
- Untuk LIVE: Gunakan interval 20-30 detik atau biarkan auto-adjust

### Manifest tidak valid di ExoPlayer:
- Pastikan `enable_pre_roll = false` untuk LIVE stream
- Pastikan ad tidak di-insert di offset 0
- Cek format manifest: `curl http://localhost:8080/fast/tenant/channel.m3u8`

### Ad muncul terlalu sering:
- Kurangi interval
- Untuk LIVE: Gunakan interval minimal 15 detik


