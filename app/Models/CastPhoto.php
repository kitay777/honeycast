<?php

// app/Models/CastPhoto.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CastPhoto extends Model
{
    protected $table = 'cast_photos';

    protected $fillable = [
        'cast_profile_id','path','sort_order','is_primary','is_blur_default', // ← 追加
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_blur_default' => 'boolean', // ← 追加
        'sort_order' => 'integer',
    ];

    public function castProfile(): BelongsTo { return $this->belongsTo(CastProfile::class); }

    public function viewPermissions(): HasMany { return $this->hasMany(CastPhotoViewPermission::class); }

    public function permissionFor(?User $viewer): ?CastPhotoViewPermission {
        if (!$viewer) return null;
        return $this->viewPermissions()->where('viewer_user_id', $viewer->id)->first();
    }

    public function viewerHasUnblurAccess(?User $viewer): bool {
        if (!$viewer) return false;
        if ($this->castProfile->user_id === $viewer->id || (method_exists($viewer,'isAdmin') && $viewer->isAdmin())) return true;
        $perm = $this->permissionFor($viewer);
        if (!$perm || $perm->status !== 'approved') return false;
        if ($perm->expires_at && now()->greaterThan($perm->expires_at)) return false;
        return true;
    }

    public function getUrlAttribute(): ?string {
        return $this->path ? \Storage::disk('public')->url($this->path) : null;
    }
}
