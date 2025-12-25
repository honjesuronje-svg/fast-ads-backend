<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ad extends Model
{
    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'name',
        'vast_url',
        'video_file_path',
        'ad_source',
        'duration_seconds',
        'ad_type',
        'click_through_url',
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

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(AdRule::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(AdVariant::class);
    }

    public function frequencyCaps(): HasMany
    {
        return $this->hasMany(FrequencyCap::class);
    }
}

