<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdPodConfig extends Model
{
    protected $fillable = [
        'tenant_id',
        'channel_id',
        'position_type',
        'min_ads',
        'max_ads',
        'max_duration_seconds',
        'fill_strategy',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}

