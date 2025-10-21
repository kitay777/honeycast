<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowGuestOrAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // ゲスト or 管理者は通す
        if (!$user || (int)($user->is_admin ?? 0) === 1) {
            return $next($request);
        }

        // 一般ログインユーザーはモーダル通知して安全な場所へ
        $redirectTo = url()->previous();
        if (!$redirectTo || $redirectTo === $request->fullUrl()) {
            $redirectTo = route('dashboard'); // フォールバック
        }

        return redirect()->to($redirectTo)->with('modal', [
            'title'   => '認証が必要です',
            'message' => 'ゲストもしくは、管理者のみが登録できます。',
            'type'    => 'warning', // 任意: 'info' | 'success' | 'error'
        ]);
    }
}