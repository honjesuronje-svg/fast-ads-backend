<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdBreak extends Model
{
    protected $fillable = [
        'channel_id',
        'position_type',
        'offset_seconds',
        'duration_seconds',
        'priority',
        'status',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}

