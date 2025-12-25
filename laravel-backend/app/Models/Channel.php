<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'hls_manifest_url',
        'ad_break_strategy',
        'ad_break_interval_seconds',
        'enable_pre_roll',
        'status',
    ];

    protected $casts = [
        'enable_pre_roll' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function adBreaks(): HasMany
    {
        return $this->hasMany(AdBreak::class);
    }
}

