<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdRule extends Model
{
    protected $fillable = [
        'ad_id',
        'rule_type',
        'rule_operator',
        'rule_value',
        'priority',
    ];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}

