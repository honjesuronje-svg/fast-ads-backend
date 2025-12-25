# Database Schema - FAST Ads Backend

## Overview
PostgreSQL/MySQL schema for Laravel Ads Control Plane. All tables include `tenant_id` for multi-tenant isolation.

## Core Tables

### 1. tenants
Stores OTT client information.

```sql
CREATE TABLE tenants (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    api_secret VARCHAR(128) NOT NULL,
    status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
    allowed_domains TEXT, -- JSON array of allowed CORS domains
    rate_limit_per_minute INT DEFAULT 1000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. channels
FAST channels per tenant.

```sql
CREATE TABLE channels (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    hls_manifest_url VARCHAR(512) NOT NULL, -- Original manifest URL
    ad_break_strategy ENUM('scte35', 'static', 'hybrid') DEFAULT 'static',
    ad_break_interval_seconds INT DEFAULT 360, -- Default ad break every 6 min
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_channel (tenant_id, slug),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3. ad_breaks
Static ad break positions for channels (when not using SCTE-35).

```sql
CREATE TABLE ad_breaks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    channel_id BIGINT UNSIGNED NOT NULL,
    position_type ENUM('pre-roll', 'mid-roll', 'post-roll') NOT NULL,
    offset_seconds INT NOT NULL, -- Seconds from start (for mid-roll)
    duration_seconds INT NOT NULL, -- Expected ad break duration
    priority INT DEFAULT 0, -- Higher priority breaks first
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    INDEX idx_channel_id (channel_id),
    INDEX idx_position (position_type, offset_seconds)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4. ad_campaigns
Ad campaigns with targeting rules.

```sql
CREATE TABLE ad_campaigns (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    budget DECIMAL(15,2), -- Optional budget limit
    status ENUM('draft', 'active', 'paused', 'completed') DEFAULT 'draft',
    priority INT DEFAULT 0, -- Higher priority campaigns served first
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_status_dates (status, start_date, end_date),
    INDEX idx_priority (priority DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 5. ads
Individual ad creatives.

```sql
CREATE TABLE ads (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    campaign_id BIGINT UNSIGNED,
    name VARCHAR(255) NOT NULL,
    vast_url VARCHAR(512) NOT NULL, -- VAST tag URL
    duration_seconds INT NOT NULL,
    ad_type ENUM('linear', 'non-linear', 'companion') DEFAULT 'linear',
    click_through_url VARCHAR(512),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES ad_campaigns(id) ON DELETE SET NULL,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 6. ad_rules
Targeting rules for ads (geo, device, time, channel).

```sql
CREATE TABLE ad_rules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    ad_id BIGINT UNSIGNED NOT NULL,
    rule_type ENUM('geo', 'device', 'time', 'channel', 'day_of_week') NOT NULL,
    rule_operator ENUM('equals', 'in', 'not_in', 'contains', 'range') NOT NULL,
    rule_value TEXT NOT NULL, -- JSON: ["US", "CA"] or {"min": 9, "max": 17}
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
    INDEX idx_ad_id (ad_id),
    INDEX idx_rule_type (rule_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 7. ad_pod_configs
Ad pod configurations (how many ads per break).

```sql
CREATE TABLE ad_pod_configs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    channel_id BIGINT UNSIGNED, -- NULL = default for all channels
    position_type ENUM('pre-roll', 'mid-roll', 'post-roll') NOT NULL,
    min_ads INT DEFAULT 1,
    max_ads INT DEFAULT 3,
    max_duration_seconds INT DEFAULT 120, -- Max total ad duration in break
    fill_strategy ENUM('strict', 'best_effort') DEFAULT 'best_effort',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    INDEX idx_tenant_channel (tenant_id, channel_id),
    INDEX idx_position (position_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 8. tracking_events
Ad tracking events (impressions, quartiles, completes).

```sql
CREATE TABLE tracking_events (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    channel_id BIGINT UNSIGNED,
    ad_id BIGINT UNSIGNED NOT NULL,
    event_type ENUM('impression', 'start', 'first_quartile', 'midpoint', 'third_quartile', 'complete', 'click', 'error') NOT NULL,
    session_id VARCHAR(128), -- Player session ID
    device_type VARCHAR(50),
    geo_country VARCHAR(2), -- ISO 3166-1 alpha-2
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp DATETIME NOT NULL,
    metadata JSON, -- Additional event data
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE SET NULL,
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_ad_id (ad_id),
    INDEX idx_event_type (event_type),
    INDEX idx_timestamp (timestamp),
    INDEX idx_tenant_timestamp (tenant_id, timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Partition by month for large datasets (PostgreSQL example)
-- CREATE TABLE tracking_events_2024_01 PARTITION OF tracking_events
--     FOR VALUES FROM ('2024-01-01') TO ('2024-02-01');
```

### 9. ad_decision_logs
Log of ad decisions made (for debugging and optimization).

```sql
CREATE TABLE ad_decision_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    channel_id BIGINT UNSIGNED NOT NULL,
    ad_break_id VARCHAR(128), -- Break identifier from Golang
    request_geo VARCHAR(2),
    request_device VARCHAR(50),
    ads_selected JSON, -- Array of ad IDs selected
    decision_time_ms INT, -- Time taken to make decision
    cache_hit BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 10. scte35_cues
SCTE-35 cue points detected in streams (for future use).

```sql
CREATE TABLE scte35_cues (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    channel_id BIGINT UNSIGNED NOT NULL,
    cue_type ENUM('splice_insert', 'time_signal', 'segmentation_descriptor') NOT NULL,
    cue_time_seconds DECIMAL(10,3) NOT NULL, -- Precise timestamp
    duration_seconds INT,
    splice_event_id INT,
    out_of_network BOOLEAN DEFAULT FALSE,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    INDEX idx_channel_time (channel_id, cue_time_seconds)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Indexes Summary

**Performance Indexes:**
- All foreign keys indexed
- Composite indexes on common query patterns:
  - `(tenant_id, slug)` on channels
  - `(status, start_date, end_date)` on campaigns
  - `(tenant_id, timestamp)` on tracking_events

**Partitioning Strategy:**
- `tracking_events`: Partition by month (PostgreSQL) or archive old data
- `ad_decision_logs`: Archive after 30 days

## Sample Data

```sql
-- Insert sample tenant
INSERT INTO tenants (name, slug, api_key, api_secret, status) VALUES
('OTT Platform A', 'ott_a', 'abc123def456', 'secret_hash_here', 'active');

-- Insert sample channel
INSERT INTO channels (tenant_id, name, slug, hls_manifest_url, ad_break_strategy) VALUES
(1, 'News Channel', 'news', 'https://cdn.example.com/news/master.m3u8', 'static');

-- Insert sample ad break
INSERT INTO ad_breaks (channel_id, position_type, offset_seconds, duration_seconds) VALUES
(1, 'pre-roll', 0, 30),
(1, 'mid-roll', 180, 60);

-- Insert sample campaign
INSERT INTO ad_campaigns (tenant_id, name, start_date, end_date, status, priority) VALUES
(1, 'Q1 2024 Campaign', '2024-01-01 00:00:00', '2024-03-31 23:59:59', 'active', 10);

-- Insert sample ad
INSERT INTO ads (tenant_id, campaign_id, name, vast_url, duration_seconds) VALUES
(1, 1, 'Brand Ad 1', 'https://adserver.com/vast.xml', 30);

-- Insert sample ad rule (geo targeting)
INSERT INTO ad_rules (ad_id, rule_type, rule_operator, rule_value) VALUES
(1, 'geo', 'in', '["US", "CA", "MX"]');

-- Insert ad pod config
INSERT INTO ad_pod_configs (tenant_id, channel_id, position_type, min_ads, max_ads, max_duration_seconds) VALUES
(1, 1, 'pre-roll', 1, 1, 30),
(1, 1, 'mid-roll', 2, 4, 120);
```

## Laravel Migrations

See `laravel-backend/database/migrations/` for Laravel migration files.

