<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    protected $fillable = [
        'name','description','image_path',
        'present_points','cast_points',
        'is_active','priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // 公開スコープ（任意）
    public function scopeActive($q) {
        return $q->where('is_active', true)->orderBy('priority')->orderBy('id','desc');
    }
}
