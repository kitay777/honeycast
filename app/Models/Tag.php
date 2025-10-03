<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model {
  protected $fillable = ['name','slug','is_active','sort_order'];
  protected $casts = ['is_active'=>'boolean','sort_order'=>'integer'];
  public function castProfiles(): BelongsToMany {
    return $this->belongsToMany(CastProfile::class, 'cast_profile_tag')->withTimestamps();
  }
}
