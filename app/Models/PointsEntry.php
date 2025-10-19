<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PointsEntry extends Model
{
    protected $table = 'points_ledger';
    protected $fillable = ['user_id','delta','balance_after','reason','acted_by'];
    public function user(){ return $this->belongsTo(User::class); }
    public function actor(){ return $this->belongsTo(User::class,'acted_by'); }
}
