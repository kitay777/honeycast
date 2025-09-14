<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopInvite extends Model
{
    protected $fillable = ['shop_id','token','expires_at','max_uses','used_count','created_by'];
    protected $casts = ['expires_at' => 'datetime'];

    public function shop(): BelongsTo { return $this->belongsTo(Shop::class); }

    public function isValid(): bool {
        if ($this->expires_at && now()->greaterThan($this->expires_at)) return false;
        if (!is_null($this->max_uses) && $this->used_count >= $this->max_uses) return false;
        return true;
    }
    public function usages(){ return $this->hasMany(\App\Models\ShopInviteUsage::class); }

}
