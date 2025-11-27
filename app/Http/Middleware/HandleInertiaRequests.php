<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

public function share(Request $request): array
{
    $user = $request->user();

    return [
        ...parent::share($request),

        'auth' => [
            // user 情報（CASTかどうかのフラグもここに含めてもOK）
            'user' => $user
                ? $user->only(['id', 'name', 'email', 'is_cast', 'is_admin', 'is_shop_owner'])
                : null,

            // ★ cast_profile を全ページで共有する（これが必要）
            'cast_profile' => $user && $user->cast_profile
                ? $user->cast_profile
                : null,
        ],

        'csrf' => csrf_token(),

        'flash' => [
            'success'     => fn () => $request->session()->get('success'),
            'error'       => fn () => $request->session()->get('error'),
            'line'        => fn () => $request->session()->get('line'),
            'line_status' => fn () => $request->session()->get('line_status'),
        ],

        'line_env' => [
            'bot_url' => config('services.line.bot_add_url'),
            'bot_qr'  => config('services.line.bot_qr'),
        ],

        'liff' => [
            'id' => config('services.line.liff_id'),
        ],
    ];
}

}
