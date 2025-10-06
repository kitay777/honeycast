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
        return [
            ...parent::share($request),

            // 認証情報（必要に応じて select で絞るのも可）
            'auth' => [
                'user' => $request->user(),
            ],

            // ★ Vue から fetch/post で使えるように常に渡す
            'csrf' => csrf_token(),

            // フラッシュ（既存 + line 用）
            'flash' => [
                'success'     => fn () => $request->session()->get('success'),
                'error'       => fn () => $request->session()->get('error'),
                'line'        => fn () => $request->session()->get('line'),          // 連携コードなど
                'line_status' => fn () => $request->session()->get('line_status'),   // 連携確認の返却
            ],

            // ★ LINE の共有はここに統一。ProfileEdit / 登録ページ / 友だち追加で全て参照可能
            'line_env' => [
                // 友だち追加（URL/QR）。config/services.php で定義：
                // 'bot_add_url' と 'bot_qr' を用意（別名 friend_url などは混乱の元）
                'bot_url' => config('services.line.bot_add_url'),   // ex: https://page.line.me/xxxxx
                'bot_qr'  => config('services.line.bot_qr'),        // 省略可
            ],

            // （任意）LIFF ID を全ページで使いたい場合
            'liff' => [
                'id' => config('services.line.liff_id'),
            ],
        ];
    }
}
