<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TextBanner extends Model
{
  protected $fillable = [
    'message','url','speed','is_active','starts_at','ends_at','priority',
    'bg_color','text_color',
  ];

  protected $casts = [
    'is_active' => 'boolean',
    'starts_at' => 'datetime',
    'ends_at'   => 'datetime',
  ];

  public function scopeActive(Builder $q): Builder {
    $now = now();
    return $q->where('is_active', true)
      ->where(function($w) use ($now){
        $w->whereNull('starts_at')->orWhere('starts_at','<=',$now);
      })
      ->where(function($w) use ($now){
        $w->whereNull('ends_at')->orWhere('ends_at','>=',$now);
      })
      ->orderBy('priority')->orderByDesc('id');
  }
}
