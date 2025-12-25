# Status Terakhir Sistem FAST Ads Backend

**Tanggal:** 25 Desember 2025, 04:10 WIB

## 📊 Data Summary

- **Ads:** 4
- **Channels:** 3 (news, antv, indosiar)
- **Campaigns:** 1
- **Ad Rules:** 0 (belum ada rules yang dibuat)
- **Tenants:** 2

## ✅ Fitur yang Sudah Berfungsi

### 1. Laravel Backend (Control Plane)
- ✅ Dashboard Admin (AdminLTE)
- ✅ Tenant Management
- ✅ Channel Management
- ✅ Ad Management (Upload Video + VAST URL)
- ✅ Campaign Management
- ✅ API Key Management
- ✅ HLS Transcoding (FFmpeg)
- ✅ VAST XML Generation
- ✅ Ad Decision API (`/api/v1/ads/decision`)
- ✅ Channel Info API (`/api/v1/channels/{tenant}/{channel}`)

### 2. Golang SSAI Service (Data Plane)
- ✅ HLS Manifest Manipulation
- ✅ Master Playlist Detection
- ✅ Media Playlist Extraction
- ✅ Ad Stitching (Pre-roll & Mid-roll)
- ✅ URL Rewriting (Relative → Absolute)
- ✅ VAST Parsing (Extract HLS from VAST)
- ✅ Redis Caching
- ✅ Laravel API Integration

### 3. Nginx Configuration
- ✅ SSL Certificates (Let's Encrypt)
- ✅ Domain Routing:
  - `ads.wkkworld.com` → Dashboard
  - `doubleclick.wkkworld.com` → API + SSAI
- ✅ Rate Limiting (configured)
- ✅ CORS Headers

### 4. Ad Rules System
- ✅ Database Table: `ad_rules` (migration sudah di-run)
- ✅ Model: `AdRule`
- ✅ Controller: `AdRuleController`
- ✅ Routes: `ads.rules.store`, `ads.rules.destroy`
- ✅ UI: Section "Ad Rules (Channel Assignment)" di Ad Show page

## ⚠️ Masalah yang Diketahui

### 1. Ad Rules UI Tidak Muncul
**Status:** File sudah ada, tapi mungkin tidak ter-load di browser

**Solusi:**
1. Hard refresh browser: `Ctrl+Shift+R` (Windows/Linux) atau `Cmd+Shift+R` (Mac)
2. Clear browser cache
3. Coba incognito/private window
4. Pastikan scroll ke bawah di halaman Ad Show

**Lokasi UI:**
- File: `/home/lamkapro/fast-ads-backend/laravel-backend/resources/views/ads/show.blade.php`
- Line: 54-200 (section "Ad Rules (Channel Assignment)")

### 2. Manifest Kadang Tidak Bisa Play
**Kemungkinan Penyebab:**
- Origin segments expired (normal untuk live stream)
- Ad segments expired
- Cache issue

**Solusi:**
```bash
# Flush Redis cache
redis-cli FLUSHALL

# Restart Golang service
cd /home/lamkapro/fast-ads-backend/golang-ssai
./restart.sh
```

### 3. Ads Tidak Muncul di Channel
**Kemungkinan Penyebab:**
- Ad tidak di-assign ke channel (belum ada Ad Rules)
- Campaign tidak aktif atau expired
- Ad status tidak aktif

**Solusi:**
1. Buat Ad Rule untuk assign ad ke channel
2. Pastikan Campaign aktif dan dalam date range
3. Pastikan Ad status = 'active'

## 📝 Cara Assign Ads ke Channel

### Via Dashboard (Recommended)
1. Login: `https://ads.wkkworld.com`
2. Buka: **Ads** → Klik Ad yang ingin di-assign
3. Scroll ke bawah → Section **"Ad Rules (Channel Assignment)"**
4. Pilih Channel(s) dari dropdown
5. Klik **"Add Rule"**

### Via SQL (Alternatif)
```sql
-- Assign Ad ID 5 ke channel 'indosiar'
INSERT INTO ad_rules (ad_id, rule_type, rule_operator, rule_value, priority, created_at, updated_at)
VALUES (5, 'channel', 'equals', 'indosiar', 0, NOW(), NOW());

-- Assign Ad ID 5 ke multiple channels
INSERT INTO ad_rules (ad_id, rule_type, rule_operator, rule_value, priority, created_at, updated_at)
VALUES (5, 'channel', 'in', '["indosiar", "antv"]', 0, NOW(), NOW());
```

## 🔗 URL Penting

- **Dashboard:** `https://ads.wkkworld.com`
- **API Base:** `https://doubleclick.wkkworld.com/api/v1`
- **SSAI Manifest:** `https://doubleclick.wkkworld.com/fast/{tenant}/{channel}.m3u8`
- **Contoh:** `https://doubleclick.wkkworld.com/fast/wkkplay/indosiar.m3u8`

## 🚀 Next Steps

1. **Test Ad Rules UI:**
   - Buka Ad di dashboard
   - Pastikan section "Ad Rules" muncul
   - Tambah Channel Rule
   - Verify ads muncul di channel

2. **Test SSAI:**
   - Assign Ad ke channel via Ad Rules
   - Test manifest di VLC
   - Verify ads muncul di stream

3. **Monitoring:**
   - Check logs: `/tmp/ssai-service.log`
   - Check Laravel logs: `storage/logs/laravel.log`
   - Monitor Redis cache

## 📞 Troubleshooting

### UI Ad Rules Tidak Muncul
```bash
# Clear Laravel cache
cd /home/lamkapro/fast-ads-backend/laravel-backend
php artisan view:clear
php artisan config:clear
php artisan cache:clear

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

### Manifest Tidak Bisa Play
```bash
# Flush Redis
redis-cli FLUSHALL

# Restart Golang service
cd /home/lamkapro/fast-ads-backend/golang-ssai
./restart.sh

# Check service status
ps aux | grep ssai-service
tail -f /tmp/ssai-service.log
```

### Ads Tidak Muncul
1. Check Ad Rules: `SELECT * FROM ad_rules WHERE ad_id = <AD_ID>;`
2. Check Campaign: `SELECT * FROM ad_campaigns WHERE id = <CAMPAIGN_ID>;`
3. Check Ad Status: `SELECT * FROM ads WHERE id = <AD_ID>;`
4. Flush cache dan restart service

---

**Last Updated:** 25 Desember 2025, 04:10 WIB

