<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CastProfile;

class CastProfilePolicy
{
    // そのキャスト本人が自分のプロフィールを管理できる判定
    public function manage(User $user, CastProfile $profile): bool {
        // 例：profilesにuser_idがあるケース
        return $profile->user_id === $user->id || $user->isAdmin(); // 既存の権限ロジックに合わせる
    }
}