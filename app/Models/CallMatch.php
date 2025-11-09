<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallMatch extends Model
{
    protected $fillable = [
        'call_request_id',
        'cast_profile_id',
        'status',
        'duration_minutes',
        'started_at',
        'ended_at',
        'latitude',
        'longitude',
    ];

    public function callRequest(): BelongsTo {
        return $this->belongsTo(CallRequest::class);
    }

    public function castProfile(): BelongsTo {
        return $this->belongsTo(CastProfile::class);
    }
}

