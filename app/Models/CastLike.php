<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CastLike extends Model
{
    protected $fillable = ['user_id','cast_profile_id'];
}