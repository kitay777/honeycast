<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // ← これ必須

class CallRequest extends Model
{
    protected $table = 'call_requests';
    protected $guarded = [];
    // これを追加（または追記）
    protected $casts = [
        'date'       => 'date',      // ← DATE列を Carbon にキャスト
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CallRequestCast::class);
    }

    // ❌ casts() だと Model の $casts と紛らわしいので改名
    public function castProfiles(): BelongsToMany
    {
        return $this->belongsToMany(CastProfile::class, 'call_request_casts')
            ->withTimestamps()
            ->withPivot(['status','note','assigned_by']);
    }
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}


