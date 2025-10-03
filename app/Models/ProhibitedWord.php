<?php

// app/Models/ProhibitedWord.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProhibitedWord extends Model
{
    protected $fillable = [
        'phrase','normalized','match_type','severity','is_active','replacement','note','created_by',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
