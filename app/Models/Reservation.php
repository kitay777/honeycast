<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'user_id','cast_profile_id','date','start_time','end_time',
        'status','payment_method','note',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function castProfile()
    {
        return $this->belongsTo(\App\Models\CastProfile::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
