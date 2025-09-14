<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tweet extends Model
{
    protected $fillable = [
        'user_id','cast_profile_id','title','body','image_path'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function castProfile() { return $this->belongsTo(CastProfile::class); }
}
