# Tracking Events Fix - 404 Error

## Masalah
Golang service mengirim tracking events tapi mendapat error 404:
```
Failed to send tracking event: unexpected status 404
```

## Penyebab
Base URL di config Golang tidak include `/api/v1`:
- Config: `base_url: "https://doubleclick.wkkworld.com"`
- URL yang dicoba: `https://doubleclick.wkkworld.com/tracking/events`
- Route Laravel: `/api/v1/tracking/events` ❌

## ✅ Perbaikan

### 1. Update Config Golang
File: `golang-ssai/configs/config.yaml`

**Sebelum:**
```yaml
laravel:
  base_url: "https://doubleclick.wkkworld.com"
```

**Sesudah:**
```yaml
laravel:
  base_url: "https://doubleclick.wkkworld.com/api/v1"
```

### 2. Restart Golang Service
```bash
cd /home/lamkapro/fast-ads-backend/golang-ssai
./restart.sh
# atau
pkill ssai-service
./bin/ssai-service
```

## Verifikasi

### 1. Test Endpoint Manual
```bash
curl -X POST https://doubleclick.wkkworld.com/api/v1/tracking/events \
  -H "X-API-Key: fast_wkkplay_976d8d2a4e9d11d92ab616ba240f2cee" \
  -H "Content-Type: application/json" \
  -d '{
    "events": [{
      "tenant_id": 1,
      "ad_id": 1,
      "event_type": "impression",
      "session_id": "test123"
    }]
  }'
```

### 2. Cek Logs Golang
```bash
tail -f /tmp/ssai-service.log | grep -i tracking
```

### 3. Test di OTT
1. Buka channel di OTT
2. Tunggu ad muncul
3. Cek tracking events:
   ```bash
   php artisan reports:check
   ```

### 4. Aggregate Reports
```bash
php artisan reports:aggregate
```

## Troubleshooting

### Masih 404?
1. **Cek config file:**
   ```bash
   cat /home/lamkapro/fast-ads-backend/golang-ssai/configs/config.yaml | grep base_url
   ```
   Harus: `base_url: "https://doubleclick.wkkworld.com/api/v1"`

2. **Cek service sudah restart:**
   ```bash
   ps aux | grep ssai-service
   ```

3. **Cek Laravel route:**
   ```bash
   php artisan route:list | grep tracking
   ```

4. **Test endpoint langsung:**
   ```bash
   curl -X POST https://doubleclick.wkkworld.com/api/v1/tracking/events \
     -H "X-API-Key: your_api_key" \
     -H "Content-Type: application/json" \
     -d '{"events": [{"tenant_id": 1, "ad_id": 1, "event_type": "impression"}]}'
   ```

### Events Masih Tidak Tercatat?
1. **Cek apakah manifest di-request:**
   - Buka channel di OTT
   - Cek logs: `tail -f /tmp/ssai-service.log`

2. **Cek apakah ads ditemukan:**
   - Logs harus menunjukkan: "Got X ads for break"

3. **Cek apakah tracking event dikirim:**
   - Cari di logs: "Failed to send tracking event" atau "Tracking event sent"

4. **Cek Laravel logs:**
   ```bash
   tail -f /home/lamkapro/fast-ads-backend/laravel-backend/storage/logs/laravel.log
   ```

## Expected Flow

1. **User buka channel di OTT**
2. **Player request manifest:** `GET /fast/{tenant}/{channel}.m3u8`
3. **Golang service:**
   - Fetch original manifest
   - Call Laravel API untuk ad decision
   - Dapat ads
   - **Kirim tracking events (impression)** ← Ini yang harus bekerja
   - Stitch ads ke manifest
   - Return manifest dengan ads
4. **Laravel API:**
   - Terima tracking event
   - Simpan ke database
5. **Aggregation:**
   - Run `php artisan reports:aggregate`
   - Generate reports

## Next Steps

Setelah fix:
1. ✅ Update config.yaml
2. ✅ Restart Golang service
3. ✅ Test di OTT
4. ✅ Cek tracking events: `php artisan reports:check`
5. ✅ Aggregate: `php artisan reports:aggregate`
6. ✅ Lihat di dashboard Reports

---

**Status**: ✅ Fixed
**Date**: 2025-12-26

