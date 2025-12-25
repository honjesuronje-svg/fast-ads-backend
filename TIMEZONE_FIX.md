# Timezone Fix - Asia/Jakarta (WIB)

## Masalah
Backend dan dashboard menggunakan UTC, sehingga report tidak sesuai dengan waktu Indonesia.

## ✅ Perbaikan yang Dilakukan

### 1. Update Timezone Configuration
- ✅ `config/app.php`: Timezone diubah dari UTC ke `Asia/Jakarta`
- ✅ `app/Console/Kernel.php`: Scheduled job menggunakan timezone `Asia/Jakarta`

### 2. Update ReportingService
- ✅ `aggregateTrackingEvents()`: Handle timezone conversion (WIB → UTC untuk query)
- ✅ `generateReport()`: Parse dates dalam timezone `Asia/Jakarta`

### 3. Update ReportDashboardController
- ✅ Default date filters menggunakan `now('Asia/Jakarta')`

### 4. Dashboard UI
- ✅ Label date picker menampilkan "(WIB)"
- ✅ Page title menunjukkan timezone

### 5. Command untuk Debug
- ✅ `php artisan reports:check` - Cek tracking events dan reports

## 🔧 Cara Menggunakan

### 1. Set Timezone di .env (Optional)
```env
APP_TIMEZONE=Asia/Jakarta
```

### 2. Clear Config Cache
```bash
cd /home/lamkapro/fast-ads-backend/laravel-backend
php artisan config:clear
php artisan config:cache
```

### 3. Cek Tracking Events
```bash
# Cek events hari ini
php artisan reports:check

# Cek events tanggal tertentu
php artisan reports:check --date=2024-12-25
```

### 4. Run Aggregation Manual
Jika events sudah ada tapi report masih 0:
```bash
# Aggregate hari ini
php artisan reports:aggregate

# Aggregate tanggal tertentu
php artisan reports:aggregate 2024-12-25
```

## 📊 Cara Verifikasi

### 1. Cek Tracking Events
```bash
php artisan tinker
>>> \App\Models\TrackingEvent::whereDate('timestamp', now('Asia/Jakarta')->toDateString())->count()
>>> \App\Models\TrackingEvent::latest()->take(5)->get(['event_type', 'ad_id', 'timestamp'])
```

### 2. Cek Reports
```bash
php artisan tinker
>>> \App\Models\AdReport::whereDate('report_date', now('Asia/Jakarta')->toDateString())->get()
```

### 3. Test di Dashboard
1. Login ke dashboard
2. Buka Reports
3. Pilih tenant
4. Pilih date range (hari ini)
5. Generate report
6. Pastikan data muncul

## ⚠️ Important Notes

1. **Database Storage**: Timestamps tetap disimpan dalam UTC (best practice)
2. **Display**: Semua display menggunakan WIB (Asia/Jakarta)
3. **Queries**: Queries mengkonversi WIB → UTC untuk query database
4. **Aggregation**: Aggregation job berjalan jam 2 AM WIB

## 🔍 Troubleshooting

### Report Masih 0 Padahal Ada Events

1. **Cek apakah events tercatat:**
   ```bash
   php artisan reports:check
   ```

2. **Jika events ada tapi report 0, run aggregation:**
   ```bash
   php artisan reports:aggregate
   ```

3. **Cek timezone di database:**
   ```bash
   php artisan tinker
   >>> config('app.timezone')
   # Should return: "Asia/Jakarta"
   ```

4. **Pastikan tracking events dikirim ke API:**
   - Endpoint: `POST /api/v1/tracking/events`
   - Pastikan Golang service atau player mengirim events

### Events Tidak Tercatat

1. **Cek API endpoint:**
   ```bash
   curl -X POST http://localhost:8000/api/v1/tracking/events \
     -H "X-API-Key: your_api_key" \
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

2. **Cek Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Cek database langsung:**
   ```sql
   SELECT * FROM tracking_events ORDER BY timestamp DESC LIMIT 10;
   ```

## 📝 Next Steps

Setelah timezone fix:
1. ✅ Clear config cache
2. ✅ Test dengan `reports:check`
3. ✅ Run aggregation jika perlu
4. ✅ Test di dashboard
5. ✅ Monitor untuk beberapa hari

---

**Status**: ✅ Timezone fix complete
**Date**: 2024-12-25
**Timezone**: Asia/Jakarta (WIB)

