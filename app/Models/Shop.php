<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    protected $fillable = ['name','code','contact_email','contact_phone','is_active','note'];

    public function invites(): HasMany { return $this->hasMany(ShopInvite::class); }
    public function users(): HasMany   { return $this->hasMany(User::class); }
}
