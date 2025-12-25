<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrequencyCap extends Model
{
    protected $fillable = [
        'tenant_id',
        'ad_id',
        'campaign_id',
        'viewer_identifier',
        'identifier_type',
        'impression_count',
        'time_window',
        'max_impressions',
        'window_start',
        'window_end',
    ];

    protected $casts = [
        'window_start' => 'datetime',
        'window_end' => 'datetime',
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
}

