<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Event extends Model
{
    protected $fillable = [
        'title','body','place','starts_at','ends_at',
        'is_all_day','is_active','priority','image_path',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_all_day'=> 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $q): Builder {
        return $q->where('is_active', true)->orderBy('starts_at')->orderBy('priority');
    }
}
