<?php

// app/Models/Game.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Game extends Model
{
    protected $fillable = [
        'title','slug','description','is_published','sort_order',
        'file_path','mime_type','size','poster_path','created_by',
    ];

    protected static function booted() {
        static::creating(function(Game $g){
            if (empty($g->slug)) {
                $base = Str::slug(mb_substr($g->title ?? 'game', 0, 50));
                $slug = $base ?: ('game-'.Str::random(6));
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $g->slug = $slug;
            }
        });
    }

    public function getUrlAttribute(): string {
        return \Storage::disk('public')->url($this->file_path);
    }
    public function getPosterUrlAttribute(): ?string {
        return $this->poster_path ? \Storage::disk('public')->url($this->poster_path) : null;
    }
}
