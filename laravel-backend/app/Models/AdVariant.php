<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdVariant extends Model
{
    protected $fillable = [
        'tenant_id',
        'ad_id',
        'name',
        'description',
        'vast_url',
        'video_file_path',
        'duration_seconds',
        'traffic_percentage',
        'priority',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function abTestAssignments(): HasMany
    {
        return $this->hasMany(AbTestAssignment::class, 'variant_id');
    }
}

