<?php

// app/Models/CastProfileViewPermission.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CastProfileViewPermission extends Model
{
    protected $fillable = [
        'cast_profile_id','viewer_user_id','granted_by_user_id','status','message','expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function castProfile(): BelongsTo {
        return $this->belongsTo(CastProfile::class);
    }
    public function viewer(): BelongsTo {
        return $this->belongsTo(User::class, 'viewer_user_id');
    }
    public function grantedBy(): BelongsTo {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }
}
