<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Hotel extends Model
{
    protected $fillable = [
        'name','area','address','phone','website_url','map_url',
        'cover_image_path','tags','is_active','priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tags'      => 'array',
    ];

    public function scopeActive(Builder $q): Builder {
        return $q->where('is_active', true)
                 ->orderBy('priority')->orderBy('name');
    }
}
