# Dashboard Reports Troubleshooting

## Masalah: Data Tidak Muncul di Dashboard

### Checklist

1. **Pastikan Tenant Dipilih**
   - Dashboard sekarang auto-select tenant pertama
   - Jika ada multiple tenants, pastikan pilih tenant yang benar
   - Reports ada untuk Tenant ID 1

2. **Cek Date Range**
   - Default: 7 hari terakhir sampai hari ini
   - Pastikan date range mencakup tanggal dimana events terjadi
   - Reports saat ini ada untuk: 2025-12-25 dan 2025-12-26

3. **Pastikan Reports Sudah Ter-Aggregate**
   ```bash
   php artisan reports:check
   php artisan reports:aggregate
   ```

4. **Cek Data di Database**
   ```bash
   php artisan tinker
   >>> \App\Models\AdReport::all()
   ```

## Cara Menggunakan Dashboard

### Step 1: Pilih Tenant
- Tenant akan auto-selected (tenant pertama)
- Atau pilih tenant manual dari dropdown

### Step 2: Pilih Date Range
- Default: 7 hari terakhir
- Atau pilih custom date range
- Pastikan mencakup tanggal events

### Step 3: Generate Report
- Klik "Generate Report"
- Data akan muncul jika:
  - Tenant dipilih ✅
  - Date range sesuai ✅
  - Reports sudah ter-aggregate ✅

## Verifikasi Data

### Cek Reports di Database
```bash
php artisan tinker
>>> \App\Models\AdReport::where('tenant_id', 1)->get()
```

### Test Report Generation
```bash
php artisan tinker
>>> $service = app(\App\Services\ReportingService::class);
>>> $report = $service->generateReport(tenantId: 1, startDate: '2025-12-25', endDate: '2025-12-25');
>>> print_r($report['totals']);
```

## Common Issues

### 1. "No data found"
**Penyebab**: 
- Tenant tidak dipilih
- Date range tidak sesuai
- Reports belum ter-aggregate

**Solusi**:
- Pilih tenant
- Adjust date range
- Run: `php artisan reports:aggregate`

### 2. Data 0 padahal ada events
**Penyebab**: Reports belum ter-aggregate

**Solusi**:
```bash
php artisan reports:aggregate
```

### 3. Tenant tidak muncul
**Penyebab**: Tenant status tidak active

**Solusi**:
```sql
UPDATE tenants SET status = 'active' WHERE id = 1;
```

## Quick Test

1. **Cek reports ada:**
   ```bash
   php artisan reports:check
   ```

2. **Jika events ada tapi reports 0:**
   ```bash
   php artisan reports:aggregate
   ```

3. **Test di dashboard:**
   - Buka: `/reports`
   - Tenant: Auto-selected
   - Date: 2025-12-25 sampai 2025-12-26
   - Generate Report
   - Data harus muncul

---

**Last Updated**: 2025-12-25

