<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CastShift extends Model
{
    protected $fillable = ['cast_profile_id','date','start_time','end_time'];

    public function castProfile()
    {
        return $this->belongsTo(CastProfile::class);
    }
}
