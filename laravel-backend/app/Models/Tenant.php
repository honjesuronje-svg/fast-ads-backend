<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'api_key',
        'api_secret',
        'status',
        'allowed_domains',
        'rate_limit_per_minute',
    ];

    protected $casts = [
        'allowed_domains' => 'array',
    ];

    protected $hidden = [
        'api_secret',
    ];

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
}

