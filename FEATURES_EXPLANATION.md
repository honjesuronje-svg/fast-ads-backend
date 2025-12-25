# Penjelasan Fitur FAST Ads Backend

## Overview
Sistem FAST Ads Backend adalah platform headless untuk mengelola iklan di Free Ad-Supported Streaming TV. Sistem ini dirancang untuk multi-tenant, artinya satu platform dapat melayani multiple OTT clients dengan isolasi data yang sempurna.

---

## 1. TENANT (Penyewa/Client OTT)

### Apa itu Tenant?
**Tenant** adalah entitas OTT client yang menggunakan platform ini. Setiap tenant adalah organisasi/perusahaan yang ingin menjalankan FAST channel dengan iklan.

### Karakteristik Tenant:
- **Isolasi Data**: Setiap tenant memiliki data yang terpisah (channels, ads, campaigns)
- **API Key Unik**: Setiap tenant memiliki API key untuk autentikasi
- **Namespace**: Setiap tenant memiliki namespace sendiri untuk channels
- **Rate Limiting**: Setiap tenant dapat memiliki rate limit sendiri

### Contoh Skenario:
```
Tenant A: "WKKWorld TV"
  - Channels: news, sports, movies
  - API Key: fast_abc123...
  - Domain: wkkworld.com

Tenant B: "StreamTV"
  - Channels: news, entertainment, kids
  - API Key: fast_xyz789...
  - Domain: streamtv.com
```

### Cara Kerja:
1. **Registrasi**: Admin membuat tenant baru di dashboard
2. **API Key Generation**: Sistem generate API key unik untuk tenant
3. **Isolasi**: Semua data (channels, ads, campaigns) terikat ke `tenant_id`
4. **Authentication**: Setiap request API harus menyertakan API key tenant
5. **Routing**: URL menggunakan tenant slug: `/fast/{tenant_slug}/{channel_slug}.m3u8`

### Database Schema:
```sql
tenants:
  - id
  - name (e.g., "WKKWorld TV")
  - slug (e.g., "wkkworld")
  - api_key (unique, untuk authentication)
  - api_secret (untuk security)
  - status (active/inactive)
  - allowed_domains (CORS whitelist)
  - rate_limit_per_minute
```

### Flow Diagram:
```
1. Admin creates Tenant "WKKWorld TV"
   ↓
2. System generates API Key: fast_abc123...
   ↓
3. Tenant uses API Key untuk semua API calls
   ↓
4. Laravel validates API Key → returns tenant_id
   ↓
5. Semua queries filtered by tenant_id
   ↓
6. Data isolation guaranteed
```

---

## 2. CHANNEL (FAST Channel)

### Apa itu Channel?
**Channel** adalah sebuah FAST (Free Ad-Supported Streaming TV) channel yang dimiliki oleh tenant. Setiap channel memiliki stream video HLS dan konfigurasi ad breaks.

### Karakteristik Channel:
- **Milik Tenant**: Setiap channel terikat ke satu tenant
- **HLS Manifest URL**: URL ke original HLS manifest dari CDN/origin
- **Ad Break Strategy**: Cara mendeteksi ad breaks (SCTE-35, static, atau hybrid)
- **Ad Break Interval**: Interval waktu untuk ad breaks (default: 6 menit)

### Contoh Skenario:
```
Tenant: WKKWorld TV
  Channels:
    - "News Channel" (slug: news)
      - HLS URL: https://cdn.wkkworld.com/hls/news/master.m3u8
      - Ad breaks: setiap 6 menit
      - Strategy: static
    
    - "Sports Channel" (slug: sports)
      - HLS URL: https://cdn.wkkworld.com/hls/sports/master.m3u8
      - Ad breaks: setiap 10 menit
      - Strategy: SCTE-35
```

### Cara Kerja:
1. **Channel Creation**: Admin membuat channel untuk tenant
2. **HLS URL Setup**: Set URL ke original manifest dari CDN
3. **Ad Break Config**: Konfigurasi kapan ad breaks muncul
4. **SSAI Processing**: 
   - Player request: `/fast/wkkworld/news.m3u8`
   - Golang service fetch original manifest dari HLS URL
   - Deteksi ad breaks (SCTE-35 atau static rules)
   - Call Laravel untuk ad decision
   - Stitch ads ke manifest
   - Return stitched manifest ke player

### Database Schema:
```sql
channels:
  - id
  - tenant_id (FK ke tenants)
  - name (e.g., "News Channel")
  - slug (e.g., "news")
  - hls_manifest_url (original manifest URL)
  - ad_break_strategy (scte35/static/hybrid)
  - ad_break_interval_seconds (default: 360 = 6 min)
  - status (active/inactive)
```

### Flow Diagram:
```
Player Request: GET /fast/wkkworld/news.m3u8
   ↓
Golang SSAI Service:
  1. Parse tenant "wkkworld" dan channel "news"
   ↓
  2. Fetch channel config dari Laravel
     - HLS URL: https://cdn.wkkworld.com/hls/news/master.m3u8
     - Ad break strategy: static
     - Ad break interval: 360 seconds
   ↓
  3. Fetch original manifest dari HLS URL
   ↓
  4. Detect ad breaks berdasarkan strategy
   ↓
  5. Untuk setiap ad break:
     - Call Laravel: POST /api/v1/ads/decision
     - Laravel returns ads berdasarkan rules
   ↓
  6. Stitch ads ke manifest
   ↓
  7. Return stitched manifest ke player
```

### Ad Break Detection:
- **SCTE-35**: Deteksi dari tags di manifest (`#EXT-X-CUE-OUT`, `#EXT-X-CUE-IN`)
- **Static**: Berdasarkan interval waktu (misalnya setiap 6 menit)
- **Hybrid**: Kombinasi SCTE-35 + static sebagai fallback

---

## 3. ADS (Iklan)

### Apa itu Ad?
**Ad** adalah creative iklan individual yang akan ditampilkan di ad breaks. Setiap ad memiliki VAST URL, duration, dan metadata.

### Karakteristik Ad:
- **VAST URL**: URL ke VAST tag dari ad server
- **Duration**: Durasi iklan dalam detik
- **Ad Type**: Linear (full-screen), non-linear (overlay), atau companion
- **Campaign**: Bisa terikat ke campaign (opsional)
- **Status**: Active atau inactive

### Contoh Skenario:
```
Ad 1: "Coca Cola 30s"
  - VAST URL: https://adserver.com/vast/cocacola.xml
  - Duration: 30 seconds
  - Type: linear
  - Campaign: "Summer Campaign 2024"

Ad 2: "Nike Banner"
  - VAST URL: https://adserver.com/vast/nike.xml
  - Duration: 15 seconds
  - Type: non-linear
  - Campaign: "Sports Campaign 2024"
```

### Cara Kerja:
1. **Ad Creation**: Admin upload/create ad dengan VAST URL
2. **Ad Rules**: Set targeting rules (geo, device, time, channel)
3. **Ad Decision**: Ketika ad break terjadi:
   - Laravel Ad Decision Service memfilter ads berdasarkan:
     - Tenant
     - Channel
     - Ad break position (pre-roll/mid-roll/post-roll)
     - Geo location (dari request header)
     - Device type
     - Time of day
     - Campaign status & budget
   - Return list of ads yang sesuai
4. **Ad Serving**: Golang service stitch ads ke manifest
5. **Tracking**: Emit tracking events (impression, quartile, complete)

### Database Schema:
```sql
ads:
  - id
  - tenant_id (FK ke tenants)
  - campaign_id (FK ke ad_campaigns, nullable)
  - name (e.g., "Coca Cola 30s")
  - vast_url (URL ke VAST tag)
  - duration_seconds (30, 60, 90, etc.)
  - ad_type (linear/non-linear/companion)
  - click_through_url (optional)
  - status (active/inactive)
```

### Ad Rules (Targeting):
```sql
ad_rules:
  - ad_id (FK ke ads)
  - rule_type (geo/device/time/channel/day_of_week)
  - rule_operator (equals/in/not_in/contains/range)
  - rule_value (JSON: ["US", "CA"] atau {"min": 9, "max": 17})
```

### Flow Diagram:
```
Ad Break Detected (mid-roll, 6 minutes)
   ↓
Golang calls Laravel: POST /api/v1/ads/decision
  {
    tenant_id: 1,
    channel: "news",
    position: "mid-roll",
    duration_seconds: 120,
    geo: "US",
    device: "smart-tv"
  }
   ↓
Laravel Ad Decision Service:
  1. Get all active ads for tenant
   ↓
  2. Filter by channel rules
   ↓
  3. Filter by geo rules (US only)
   ↓
  4. Filter by device rules (smart-tv)
   ↓
  5. Filter by time rules (current time)
   ↓
  6. Filter by campaign status & budget
   ↓
  7. Select ads untuk fill 120 seconds
   ↓
  8. Return ads dengan tracking URLs
   ↓
Golang stitches ads ke manifest
   ↓
Player receives manifest dengan ads
```

### Ad Selection Logic:
- **Priority**: Campaigns dengan priority lebih tinggi dipilih dulu
- **Budget**: Campaigns dengan budget tersisa
- **Duration Matching**: Pilih ads yang total duration mendekati requested duration
- **Fill Strategy**: 
  - `strict`: Harus exact match duration
  - `best_effort`: Fill sebanyak mungkin tanpa melebihi max duration

---

## 4. CAMPAIGN (Kampanye Iklan)

### Apa itu Campaign?
**Campaign** adalah kumpulan ads yang diorganisir untuk tujuan marketing tertentu. Campaign memiliki budget, targeting rules, dan schedule.

### Karakteristik Campaign:
- **Date Range**: Start date dan end date
- **Budget**: Total budget untuk campaign (opsional)
- **Priority**: Prioritas campaign (higher priority = served first)
- **Status**: Draft, active, paused, atau completed
- **Multiple Ads**: Satu campaign bisa berisi multiple ads

### Contoh Skenario:
```
Campaign: "Summer Sale 2024"
  - Tenant: WKKWorld TV
  - Start: 2024-06-01
  - End: 2024-08-31
  - Budget: $50,000
  - Priority: 10 (high)
  - Status: active
  - Ads:
    - "Coca Cola Summer" (30s)
    - "Nike Summer Collection" (15s)
    - "McDonald's Summer Menu" (30s)
```

### Cara Kerja:
1. **Campaign Creation**: Admin membuat campaign dengan date range dan budget
2. **Ad Assignment**: Assign ads ke campaign
3. **Campaign Activation**: Set status ke "active"
4. **Ad Decision Integration**:
   - Ketika ad break terjadi, Laravel memfilter campaigns:
     - Date range valid (current date between start & end)
     - Status = active
     - Budget tersisa (jika ada budget limit)
     - Priority sorting
   - Select ads dari active campaigns
5. **Budget Tracking**: (Future) Track spending per campaign
6. **Campaign Completion**: Auto-pause ketika end date reached atau budget exhausted

### Database Schema:
```sql
ad_campaigns:
  - id
  - tenant_id (FK ke tenants)
  - name (e.g., "Summer Sale 2024")
  - description
  - start_date
  - end_date
  - budget (optional, DECIMAL)
  - status (draft/active/paused/completed)
  - priority (INT, higher = served first)
```

### Flow Diagram:
```
Ad Break Request
   ↓
Laravel Ad Decision Service:
  1. Get all campaigns for tenant
   ↓
  2. Filter by date range
     - Current date >= start_date
     - Current date <= end_date
   ↓
  3. Filter by status = 'active'
   ↓
  4. Filter by budget (if budget exists)
     - Check if budget > 0
   ↓
  5. Sort by priority (DESC)
   ↓
  6. Get ads from active campaigns
   ↓
  7. Apply ad rules (geo, device, time)
   ↓
  8. Select ads untuk fill ad break
   ↓
  9. Return ads dengan campaign info
```

### Campaign Priority:
- **Higher Priority = Served First**: Campaign dengan priority 10 akan dipilih sebelum priority 5
- **Use Case**: 
  - Premium campaigns: priority 10
  - Standard campaigns: priority 5
  - Fill campaigns: priority 1

### Budget Management:
- **Budget Tracking**: (Future feature) Track impressions dan spending
- **Auto-Pause**: Campaign auto-pause ketika budget exhausted
- **Budget Alerts**: (Future) Notify ketika budget mencapai threshold

---

## Hubungan Antar Fitur

### Hierarchy:
```
Tenant (1)
  ├── Channels (many)
  │     └── Ad Breaks (many)
  ├── Campaigns (many)
  │     └── Ads (many)
  └── Ads (many, bisa standalone atau dalam campaign)
```

### Data Flow:
```
1. Tenant dibuat → Generate API Key
   ↓
2. Channel dibuat untuk tenant → Set HLS URL & ad break config
   ↓
3. Campaign dibuat untuk tenant → Set date range & budget
   ↓
4. Ads dibuat → Assign ke campaign (optional) → Set targeting rules
   ↓
5. Player request manifest → Golang detect ad break
   ↓
6. Golang call Laravel → Laravel filter ads berdasarkan:
   - Tenant
   - Channel
   - Campaign (active, date range, budget)
   - Ad rules (geo, device, time)
   ↓
7. Return ads → Golang stitch → Player receive manifest dengan ads
```

---

## Contoh Use Case Lengkap

### Scenario: WKKWorld TV ingin menjalankan campaign iklan di News Channel

**Step 1: Setup Tenant**
```
Name: WKKWorld TV
Slug: wkkworld
API Key: fast_wkkworld_abc123...
Status: active
```

**Step 2: Create Channel**
```
Tenant: WKKWorld TV
Name: News Channel
Slug: news
HLS URL: https://cdn.wkkworld.com/hls/news/master.m3u8
Ad Break Strategy: static
Ad Break Interval: 360 seconds (6 minutes)
```

**Step 3: Create Campaign**
```
Tenant: WKKWorld TV
Name: Summer Sale 2024
Start Date: 2024-06-01
End Date: 2024-08-31
Budget: $50,000
Priority: 10
Status: active
```

**Step 4: Create Ads**
```
Ad 1:
  - Campaign: Summer Sale 2024
  - Name: Coca Cola 30s
  - VAST URL: https://adserver.com/vast/cocacola.xml
  - Duration: 30 seconds
  - Rules: Geo = US, Device = smart-tv

Ad 2:
  - Campaign: Summer Sale 2024
  - Name: Nike 15s
  - VAST URL: https://adserver.com/vast/nike.xml
  - Duration: 15 seconds
  - Rules: Geo = US, CA, Device = all
```

**Step 5: Ad Serving Flow**
```
1. Player di Smart TV (US) request:
   GET /fast/wkkworld/news.m3u8

2. Golang SSAI Service:
   - Fetch original manifest
   - Detect ad break di 6 minutes
   - Call Laravel: POST /api/v1/ads/decision
     {
       tenant_id: 1,
       channel: "news",
       position: "mid-roll",
       duration_seconds: 120,
       geo: "US",
       device: "smart-tv"
     }

3. Laravel Ad Decision:
   - Filter campaigns: Summer Sale 2024 (active, date valid)
   - Filter ads: Coca Cola 30s, Nike 15s (match geo & device)
   - Select ads untuk fill 120 seconds
   - Return: [Coca Cola 30s, Nike 15s, ...]

4. Golang:
   - Stitch ads ke manifest
   - Return stitched manifest

5. Player:
   - Play content → Ad break → Play ads → Continue content
```

---

## Best Practices

### 1. Tenant Management
- ✅ Satu tenant per OTT client
- ✅ Gunakan slug yang descriptive dan unique
- ✅ Rotate API keys secara berkala
- ✅ Monitor rate limits per tenant

### 2. Channel Management
- ✅ Set HLS URL yang reliable dan fast
- ✅ Configure ad break strategy sesuai content
- ✅ Test ad break detection sebelum production
- ✅ Monitor channel performance

### 3. Ad Management
- ✅ Organize ads dalam campaigns
- ✅ Set targeting rules yang spesifik
- ✅ Test VAST URLs sebelum activate
- ✅ Monitor ad performance (impressions, completions)

### 4. Campaign Management
- ✅ Set realistic budgets
- ✅ Use priority untuk control ad serving
- ✅ Monitor campaign performance
- ✅ Pause campaigns yang tidak perform

---

## Summary

| Fitur | Purpose | Key Attributes |
|-------|---------|----------------|
| **Tenant** | Multi-tenant isolation | API Key, Namespace, Rate Limit |
| **Channel** | FAST channel dengan HLS stream | HLS URL, Ad Break Strategy, Interval |
| **Ad** | Individual ad creative | VAST URL, Duration, Type, Rules |
| **Campaign** | Organize ads untuk marketing | Date Range, Budget, Priority, Status |

Semua fitur bekerja bersama untuk menyediakan sistem ad serving yang scalable, flexible, dan production-ready untuk FAST channels.

