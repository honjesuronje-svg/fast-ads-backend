<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdReport extends Model
{
    protected $fillable = [
        'tenant_id',
        'ad_id',
        'campaign_id',
        'channel_id',
        'variant_id',
        'report_date',
        'time_granularity',
        'impressions',
        'starts',
        'completions',
        'clicks',
        'completion_rate',
        'click_through_rate',
        'revenue',
        'unique_viewers',
        'avg_duration_watched',
        'total_duration_watched',
    ];

    protected $casts = [
        'report_date' => 'date',
        'completion_rate' => 'decimal:2',
        'click_through_rate' => 'decimal:2',
        'revenue' => 'decimal:2',
        'avg_duration_watched' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(AdVariant::class);
    }
}

