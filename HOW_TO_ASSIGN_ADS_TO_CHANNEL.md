# Cara Assign Ads ke Channel

## Overview

Sistem FAST Ads menggunakan **Ad Rules** untuk menentukan channel mana yang bisa menampilkan ads. Ada beberapa cara untuk assign ads ke channel:

## Cara 1: Via Ad Rules (Recommended untuk Channel Spesifik)

**Ad Rules** memungkinkan Anda menentukan channel spesifik yang bisa menampilkan ads.

### Langkah-langkah:

1. **Buat Ad** (jika belum ada):
   - Dashboard → Ads → Create New Ad
   - Upload video atau masukkan VAST URL
   - Set Tenant, Duration, Status, dll

2. **Buat Campaign** (opsional tapi recommended):
   - Dashboard → Campaigns → Create New Campaign
   - Pilih Tenant
   - Set Start Date & End Date
   - Set Status: **Active**
   - Assign Ad ke Campaign (Edit Ad → Pilih Campaign)

3. **Tambahkan Ad Rule untuk Channel**:
   - **Via Database langsung** (sementara, sampai UI dibuat):
     ```sql
     INSERT INTO ad_rules (ad_id, rule_type, rule_operator, rule_value, priority, created_at, updated_at)
     VALUES (
         <AD_ID>,                    -- ID dari ad yang ingin di-assign
         'channel',                  -- Rule type: channel
         'equals',                   -- Operator: equals (untuk 1 channel) atau 'in' (untuk multiple channels)
         'indosiar',                 -- Channel slug (contoh: 'indosiar', 'antv')
         0,                          -- Priority
         NOW(),
         NOW()
     );
     ```
   
   - **Untuk multiple channels**:
     ```sql
     INSERT INTO ad_rules (ad_id, rule_type, rule_operator, rule_value, priority, created_at, updated_at)
     VALUES (
         <AD_ID>,
         'channel',
         'in',                       -- Operator: in (untuk multiple channels)
         '["indosiar", "antv"]',     -- JSON array of channel slugs
         0,
         NOW(),
         NOW()
     );
     ```

### Contoh:

```sql
-- Assign Ad ID 5 ke channel 'indosiar'
INSERT INTO ad_rules (ad_id, rule_type, rule_operator, rule_value, priority, created_at, updated_at)
VALUES (5, 'channel', 'equals', 'indosiar', 0, NOW(), NOW());

-- Assign Ad ID 5 ke multiple channels
INSERT INTO ad_rules (ad_id, rule_type, rule_operator, rule_value, priority, created_at, updated_at)
VALUES (5, 'channel', 'in', '["indosiar", "antv"]', 0, NOW(), NOW());
```

## Cara 2: Via Campaign (Semua Channel Tenant)

Jika Anda ingin ads muncul di **SEMUA channel** dari tenant tertentu:

1. **Buat Campaign**:
   - Dashboard → Campaigns → Create New Campaign
   - Pilih Tenant (contoh: `wkkplay`)
   - Set Start Date & End Date
   - Set Status: **Active**

2. **Assign Ad ke Campaign**:
   - Dashboard → Ads → Edit Ad
   - Pilih Campaign dari dropdown
   - Save

3. **Hasil**: Ads akan muncul di **SEMUA channel** dari tenant tersebut (tanpa perlu Ad Rules).

## Cara 3: Tanpa Rules (Semua Channel - Tidak Recommended)

Jika Ad dibuat **tanpa Campaign dan tanpa Rules**, ads akan muncul di **semua channel** (tidak recommended untuk production).

## Troubleshooting

### Ads tidak muncul di channel:

1. **Cek Ad Rules**:
   ```sql
   SELECT * FROM ad_rules WHERE ad_id = <AD_ID>;
   ```
   - Pastikan ada rule dengan `rule_type = 'channel'`
   - Pastikan `rule_value` sesuai dengan channel slug

2. **Cek Campaign**:
   ```sql
   SELECT * FROM ad_campaigns WHERE id = <CAMPAIGN_ID>;
   ```
   - Pastikan `status = 'active'`
   - Pastikan `start_date <= NOW()` dan `end_date >= NOW()`

3. **Cek Ad Status**:
   ```sql
   SELECT * FROM ads WHERE id = <AD_ID>;
   ```
   - Pastikan `status = 'active'`

4. **Flush Cache**:
   ```bash
   redis-cli FLUSHALL
   ```

5. **Restart Golang Service**:
   ```bash
   cd /home/lamkapro/fast-ads-backend/golang-ssai
   ./restart.sh
   ```

## Quick Reference

| Method | Channel Specific? | Recommended? |
|--------|------------------|--------------|
| Ad Rules (channel) | ✅ Yes | ✅ Yes |
| Campaign (no rules) | ❌ No (all channels) | ⚠️ Sometimes |
| No Campaign, No Rules | ❌ No (all channels) | ❌ No |

## Contoh SQL untuk Assign Ad ke Channel

```sql
-- 1. Cek channel slug yang tersedia
SELECT id, name, slug FROM channels WHERE tenant_id = 1;

-- 2. Cek ad yang tersedia
SELECT id, name, campaign_id, status FROM ads WHERE tenant_id = 1;

-- 3. Assign Ad ID 5 ke channel 'indosiar'
INSERT INTO ad_rules (ad_id, rule_type, rule_operator, rule_value, priority, created_at, updated_at)
VALUES (5, 'channel', 'equals', 'indosiar', 0, NOW(), NOW());

-- 4. Verify
SELECT ar.*, a.name as ad_name, a.status as ad_status
FROM ad_rules ar
JOIN ads a ON ar.ad_id = a.id
WHERE ar.rule_type = 'channel' AND ar.rule_value = 'indosiar';
```

## Catatan Penting

1. **Channel Slug**: Pastikan `rule_value` sesuai dengan channel slug (bukan channel name)
2. **Campaign Status**: Campaign harus `active` dan dalam date range
3. **Ad Status**: Ad harus `active`
4. **Cache**: Perubahan Ad Rules mungkin perlu flush cache
5. **Multiple Rules**: Jika Ad memiliki multiple rules, **semua rules harus match** (AND logic)

