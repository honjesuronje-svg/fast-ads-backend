# Tracking Events Guide

## Overview

Tracking events digunakan untuk mencatat interaksi user dengan ads (impressions, starts, completions, dll). Events ini kemudian di-aggregate menjadi reports.

## 🔄 Alur Tracking Events

### 1. Dari Player/OTT
```
Player → Golang SSAI Service → Laravel API → Database
```

### 2. Dari Golang Service (Auto)
```
Manifest Request → Ad Decision → Tracking Event (impression) → Laravel API
```

## 📊 Event Types

- `impression` - Ad ditampilkan
- `start` - Ad mulai diputar
- `first_quartile` - 25% ad selesai
- `midpoint` - 50% ad selesai
- `third_quartile` - 75% ad selesai
- `complete` - Ad selesai diputar
- `click` - User klik ad
- `error` - Error saat memutar ad

## 🔧 Cara Tracking Events Bekerja

### 1. Automatic Tracking (Golang Service)

Ketika manifest di-request, Golang service otomatis:
1. Memanggil Laravel API untuk ad decision
2. Mendapatkan ads untuk ad break
3. **Otomatis mengirim impression event** untuk setiap ad yang di-stitch

**File**: `golang-ssai/internal/handler/manifest.go`
```go
// emitTrackingEvents sends tracking events for ad impressions
func (h *ManifestHandler) emitTrackingEvents(tenantID int, channel string, ads []models.Ad, c *gin.Context) {
    for _, ad := range ads {
        event := models.TrackingEvent{
            TenantID:   tenantID,
            AdID:       ad.AdID,
            EventType:  "impression",
            SessionID:  c.Query("session_id"),
            // ... other fields
        }
        h.laravelClient.SendTrackingEvent(context.Background(), event)
    }
}
```

### 2. Manual Tracking (Player/OTT)

Player bisa mengirim tracking events langsung ke Laravel API:

**Endpoint**: `POST /api/v1/tracking/events`

**Request**:
```json
{
  "events": [
    {
      "tenant_id": 1,
      "ad_id": 1,
      "channel_id": 1,
      "event_type": "impression",
      "session_id": "abc123",
      "device_type": "mobile",
      "geo_country": "ID",
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0...",
      "timestamp": "2025-12-26T01:30:00Z",
      "metadata": {}
    }
  ]
}
```

**Headers**:
```
X-API-Key: your_api_key
Content-Type: application/json
```

## 🧪 Testing

### 1. Test Manual Tracking Event

```bash
cd /home/lamkapro/fast-ads-backend/laravel-backend
php artisan tracking:test --tenant_id=1 --ad_id=1
```

### 2. Test via API

```bash
curl -X POST http://localhost:8000/api/v1/tracking/events \
  -H "X-API-Key: test_api_key_123" \
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

### 3. Cek Tracking Events

```bash
# Cek events hari ini
php artisan reports:check

# Cek semua events
php artisan tinker
>>> \App\Models\TrackingEvent::latest()->take(10)->get()
```

## 📈 Aggregation ke Reports

Tracking events di-aggregate menjadi reports:

### Manual Aggregation
```bash
# Aggregate hari ini
php artisan reports:aggregate

# Aggregate tanggal tertentu
php artisan reports:aggregate 2025-12-25
```

### Automatic Aggregation
Scheduled job berjalan setiap hari jam 2 AM WIB:
```php
// app/Console/Kernel.php
$schedule->command('reports:aggregate')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta');
```

## ⚠️ Troubleshooting

### Problem: Report Masih 0 Padahal Ada Events

**Solusi**:
1. Cek events ada:
   ```bash
   php artisan reports:check
   ```

2. Run aggregation:
   ```bash
   php artisan reports:aggregate
   ```

3. Refresh dashboard reports

### Problem: Events Tidak Tercatat

**Cek**:
1. **Golang service mengirim events?**
   - Cek logs: `tail -f /tmp/ssai-service.log`
   - Cari: "Failed to send tracking event"

2. **API endpoint bekerja?**
   ```bash
   curl -X POST http://localhost:8000/api/v1/tracking/events \
     -H "X-API-Key: test_api_key_123" \
     -H "Content-Type: application/json" \
     -d '{"events": [{"tenant_id": 1, "ad_id": 1, "event_type": "impression"}]}'
   ```

3. **Database connection?**
   ```bash
   php artisan tinker
   >>> \App\Models\TrackingEvent::count()
   ```

### Problem: Events dengan Tanggal Lama

**Kemungkinan**:
- Events dikirim tanpa timestamp, menggunakan default
- Timezone tidak di-set dengan benar

**Solusi**:
- Pastikan timestamp dikirim dalam request
- Atau Laravel akan menggunakan `now()` dengan timezone `Asia/Jakarta`

## 📝 Best Practices

1. **Selalu kirim timestamp** dalam request tracking events
2. **Gunakan session_id** untuk tracking unique viewers
3. **Kirim semua event types** (impression, start, complete, dll)
4. **Run aggregation** setelah ada banyak events
5. **Monitor logs** untuk error tracking events

## 🔍 Debugging Commands

```bash
# Cek total events
php artisan tinker
>>> \App\Models\TrackingEvent::count()

# Cek events hari ini
php artisan reports:check

# Cek events dengan detail
php artisan tinker
>>> \App\Models\TrackingEvent::latest()->take(5)->get(['event_type', 'ad_id', 'timestamp', 'session_id'])

# Cek reports
php artisan tinker
>>> \App\Models\AdReport::whereDate('report_date', now('Asia/Jakarta')->toDateString())->get()
```

## 📚 Related Files

- `app/Services/TrackingService.php` - Service untuk record events
- `app/Http/Controllers/TrackingController.php` - API endpoint
- `app/Services/ReportingService.php` - Aggregation service
- `golang-ssai/internal/handler/manifest.go` - Auto tracking dari Golang
- `golang-ssai/internal/client/laravel.go` - Client untuk kirim events

---

**Last Updated**: 2025-12-26

