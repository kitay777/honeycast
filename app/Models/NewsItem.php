<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class NewsItem extends Model
{
    protected $fillable = ['title','body','url','published_at','is_active','priority'];
    protected $casts = ['is_active'=>'boolean','published_at'=>'datetime'];

    public function scopeActive(Builder $q): Builder {
        $now = now();
        return $q->where('is_active', true)
                 ->where(function($w) use ($now){
                     $w->whereNull('published_at')->orWhere('published_at','<=',$now);
                 })
                 ->orderBy('priority')->orderByDesc('published_at')->orderByDesc('id');
    }
}
