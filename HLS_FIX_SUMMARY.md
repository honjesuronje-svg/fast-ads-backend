# HLS Fix Summary - Service Restart Issue

## Masalah
Setelah restart Golang service, HLS tidak bisa diakses (error 500).

## Penyebab
URL construction di client code tidak konsisten:
- Config: `base_url: "https://doubleclick.wkkworld.com/api/v1"` (include `/api/v1`)
- Client code: `fmt.Sprintf("%s/api/v1/...", baseURL)` (tambah `/api/v1` lagi)
- Hasil: Double `/api/v1` → `https://doubleclick.wkkworld.com/api/v1/api/v1/...` ❌

## ✅ Perbaikan

### 1. Update Config
File: `golang-ssai/configs/config.yaml`

**Sebelum:**
```yaml
laravel:
  base_url: "https://doubleclick.wkkworld.com/api/v1"
```

**Sesudah:**
```yaml
laravel:
  base_url: "https://doubleclick.wkkworld.com"
```

### 2. Update Client Code
File: `golang-ssai/internal/client/laravel.go`

**SendTrackingEvent:**
```go
// Sebelum
url := fmt.Sprintf("%s/tracking/events", c.baseURL)

// Sesudah
url := fmt.Sprintf("%s/api/v1/tracking/events", c.baseURL)
```

**GetAdDecision & GetChannelBySlug:**
- Sudah benar: `fmt.Sprintf("%s/api/v1/...", c.baseURL)`
- Tidak perlu diubah

## Verifikasi

### 1. Test HLS Manifest
```bash
curl http://localhost:8080/fast/wkkplay/indosiar.m3u8
```
✅ Harus return manifest dengan ads

### 2. Test Tracking Events
```bash
# Cek events setelah melihat ad
cd /home/lamkapro/fast-ads-backend/laravel-backend
php artisan reports:check
```

### 3. Cek Logs
```bash
tail -f /tmp/ssai-service.log | grep -i "tracking\|error"
```
✅ Tidak ada error 404 untuk tracking events

## Status
- ✅ HLS manifest: Bekerja
- ✅ Channel info: Bekerja
- ✅ Ad decision: Bekerja
- ✅ Tracking events: Seharusnya bekerja (perlu test di OTT)

## Next Steps
1. Test di OTT: Buka channel dan lihat ad
2. Cek tracking events: `php artisan reports:check`
3. Aggregate reports: `php artisan reports:aggregate`
4. Lihat di dashboard Reports

---

**Status**: ✅ Fixed
**Date**: 2025-12-25

