# Penjelasan Dashboard Reports

## Screenshot Dashboard

Dashboard menampilkan 8 metric cards yang menunjukkan performa ads:

### Row 1 (Baris Atas):

1. **Total Impressions: 1** 🟢
   - Jumlah total ad yang ditampilkan ke viewers
   - Icon: Mata hijau
   - Artinya: Ada 1 ad yang sudah ditampilkan

2. **Starts: 0** 🔵
   - Jumlah ad yang mulai diputar
   - Icon: Tombol play biru
   - Artinya: Belum ada ad yang mulai diputar (hanya impression)

3. **Completions: 0** 🟡
   - Jumlah ad yang selesai diputar sampai akhir
   - Icon: Checkmark kuning
   - Artinya: Belum ada ad yang selesai diputar

4. **Completion Rate: 0.00%** 🔴
   - Persentase ad yang selesai dari total impressions
   - Formula: (Completions / Impressions) × 100%
   - Artinya: 0% karena belum ada completion

### Row 2 (Baris Bawah):

5. **Clicks: 0** 🔵
   - Jumlah klik pada ad
   - Icon: Mouse pointer biru
   - Artinya: Belum ada yang klik ad

6. **CTR (Click-Through Rate): 0.00%** 🔵
   - Persentase klik dari total impressions
   - Formula: (Clicks / Impressions) × 100%
   - Artinya: 0% karena belum ada klik

7. **Unique Viewers: 1** 🔵
   - Jumlah viewer unik yang melihat ads
   - Icon: Group of people biru
   - Artinya: Ada 1 viewer unik

8. **Avg Duration: 00:00:00** 🔵
   - Rata-rata durasi ad yang ditonton
   - Icon: Jam biru
   - Artinya: Belum ada durasi karena belum ada start/completion

## Analisis Data

### Status Saat Ini:
- ✅ **1 Impression** - Ad sudah ditampilkan
- ❌ **0 Starts** - Ad belum mulai diputar
- ❌ **0 Completions** - Ad belum selesai
- ✅ **1 Unique Viewer** - Ada 1 viewer

### Interpretasi:
1. **Ad sudah muncul** (impression tercatat)
2. **Tapi ad belum diputar** (starts = 0)
3. **Kemungkinan penyebab:**
   - Player belum trigger "start" event
   - Ad tidak otomatis play
   - Tracking events "start" belum dikirim

## Masalah Tenant yang Salah

### Masalah:
- Anda nonton di tenant **"wkkworld tv"** (wkkplay)
- Tapi tercatat di tenant **"OTT Platform A"** (tenant_id: 1)

### Penyebab:
Tracking events menggunakan tenantID yang salah. TenantID seharusnya diambil dari channelInfo, tapi mungkin ada bug di kode.

### ✅ Perbaikan yang Sudah Dilakukan:
1. ✅ TenantID sekarang diambil langsung dari `channelInfo.TenantID`
2. ✅ ChannelID juga diambil dari `channelInfo.ID` (bukan hardcoded 1)
3. ✅ Debug logging ditambahkan untuk tracking

### Verifikasi:
Setelah restart Golang service, tracking events seharusnya menggunakan tenant_id yang benar (3 untuk wkkworld tv).

## Cara Membaca Dashboard

### Metrics Penting:

1. **Impressions** - Berapa banyak ad ditampilkan
   - Target: Tinggi (semakin banyak semakin baik)

2. **Completion Rate** - Berapa % ad yang ditonton sampai selesai
   - Target: > 70% (artinya 70%+ viewers menonton sampai selesai)
   - Saat ini: 0% (belum ada completion)

3. **CTR** - Berapa % yang klik ad
   - Target: > 1% (artinya 1%+ viewers klik ad)
   - Saat ini: 0% (belum ada klik)

4. **Unique Viewers** - Berapa banyak viewer unik
   - Target: Tinggi (semakin banyak semakin baik)

### Tips:
- **Completion Rate rendah** = Ad mungkin terlalu panjang atau tidak menarik
- **CTR rendah** = Call-to-action mungkin kurang jelas
- **Impressions tinggi tapi Completion rendah** = Ad mungkin tidak relevan

## Next Steps

1. **Test lagi di OTT** dengan tenant wkkworld tv
2. **Cek tracking events** apakah sekarang menggunakan tenant_id yang benar
3. **Pastikan player mengirim "start" dan "complete" events** untuk metrics yang lebih lengkap

---

**Last Updated**: 2025-12-25

