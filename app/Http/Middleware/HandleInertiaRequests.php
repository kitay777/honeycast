<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
                    // 友だち追加導線（常に props で渡す）
            'line' => [
                'friend_url' => config('services.line.friend_url'),
                'friend_qr'  => config('services.line.friend_qr'),
            ],

            // セッションフラッシュに 'line' を載せて Vue 側で拾えるように
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'line'    => fn () => $request->session()->get('line'),        // ★ 追加
                'line_status' => fn () => $request->session()->get('line_status'), // 使うなら
            ],
            'line_env' => [
                'bot_url' => config('services.line.bot_add_url'), // 例: https://line.me/R/ti/p/@YOUR_BOT_ID
                'bot_qr'  => config('services.line.bot_qr'),      // 任意
            ],
        ];
    }
}
