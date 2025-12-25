<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbTestAssignment extends Model
{
    protected $fillable = [
        'tenant_id',
        'ad_id',
        'variant_id',
        'viewer_identifier',
        'identifier_type',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(AdVariant::class, 'variant_id');
    }
}

