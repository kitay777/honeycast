<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftSend extends Model
{
    // ★ create() で入れるカラムをすべて許可
    protected $fillable = [
        'sender_user_id',
        'cast_profile_id',
        'gift_id',
        'present_points',
        'cast_points',
        'message',
    ];

    // （任意）型キャスト
    protected $casts = [
        'present_points' => 'integer',
        'cast_points'    => 'integer',
    ];

    // リレーション（任意）
    public function sender(){ return $this->belongsTo(\App\Models\User::class,'sender_user_id'); }
    public function cast(){ return $this->belongsTo(\App\Models\CastProfile::class,'cast_profile_id'); }
    public function gift(){ return $this->belongsTo(\App\Models\Gift::class); }
}
