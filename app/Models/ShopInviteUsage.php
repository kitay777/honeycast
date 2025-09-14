<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopInviteUsage extends Model
{
    // デフォルトで table 名は shop_invite_usages になるので明示不要
    protected $fillable = ['shop_invite_id','user_id','ip','user_agent'];

    public function invite(): BelongsTo { return $this->belongsTo(ShopInvite::class); }
    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
}
