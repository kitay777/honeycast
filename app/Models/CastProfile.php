<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;


class CastProfile extends Model
{
    protected $fillable = [
        'user_id','nickname','rank','age','height_cm','cup','style','alcohol','mbti',
        'area','tags','freeword','photo_path',
        'is_blur_default',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function shifts()
    {
        return $this->hasMany(CastShift::class);
    }
    public function callAssignments()
    {
        return $this->hasMany(\App\Models\CallRequestCast::class);
    }
    public function viewPermissions(): HasMany {
        return $this->hasMany(CastProfileViewPermission::class);
    }

    public function permissionFor(User $viewer): ?CastProfileViewPermission {
        return $this->viewPermissions()
            ->where('viewer_user_id', $viewer->id)
            ->first();
    }

    /**
     * 閲覧者が「非ぼかしを見れる」か？
     */
    public function viewerHasUnblurAccess(?User $viewer): bool {
        if (!$viewer) return false;
        $perm = $this->permissionFor($viewer);
        if (!$perm) return false;
        if ($perm->status !== 'approved') return false;
        if ($perm->expires_at && now()->greaterThan($perm->expires_at)) return false;
        return true;
    }
}
