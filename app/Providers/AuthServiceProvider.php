<?php

namespace App\Providers;

use App\Models\User; // ← 追加
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    // app/Providers/AuthServiceProvider.php
    protected $policies = [
        \App\Models\CastProfile::class => \App\Policies\CastProfilePolicy::class,
    ];


    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('admin', fn(User $user) => (bool) $user->is_admin);

        // 管理者にもオーナー画面を許可したいなら「|| $user->is_admin」を追加
        Gate::define('shop-owner', fn(User $user) =>
            (bool) $user->is_shop_owner && !is_null($user->shop_id)
             || $user->is_admin   // ← 管理者もOKにするならコメント外す
        );

        // もし「管理者は全権限パス」にしたいなら（任意）
        // Gate::before(fn(User $user) => $user->is_admin ? true : null);
    }
}
