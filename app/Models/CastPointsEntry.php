<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CastPointsEntry extends Model {
  protected $table='cast_points_ledger';
  protected $fillable=['cast_profile_id','delta','balance_after','reason','acted_by'];
  public function cast(){ return $this->belongsTo(\App\Models\CastProfile::class,'cast_profile_id');}
}

