<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'area',
        'phone',
        'is_admin',
        'shop_id',
        'is_cast',
        'nickname','rank','age','height_cm','cup','style','alcohol','mbti',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'shop_id'  => 'integer',
            'is_admin' => 'boolean',
            'is_shop_owner' => 'boolean',
            'is_cast' => 'boolean',
        ];
    }
    public function castProfile()
    {
        return $this->hasOne(\App\Models\CastProfile::class);
    }
    public function shop()
    {
        return $this->belongsTo(\App\Models\Shop::class);
    }

    public function scopeCasts($q)
    {
        return $q->where('is_cast', true);
    }
    public function castLikes()
    {
        return $this->hasMany(\App\Models\CastLike::class);
    }
    public function pointsEntries()
    {
        return $this->hasMany(\App\Models\PointsEntry::class);
    }
    protected $casts = [
        'last_login_at' => 'datetime',
    ];

}
