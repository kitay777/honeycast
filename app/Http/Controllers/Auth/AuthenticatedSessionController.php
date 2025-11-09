<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        // ✅ 入力欄は共通（login）
        $request->validate([
            'login'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = trim((string)$request->input('login'));
        $password = (string)$request->input('password');

        // ✅ 入力がメールアドレス形式か電話番号形式か判定
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            // 📧 メールログイン
            $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($login)])->first();
        } elseif (preg_match('/^[0-9]{10,11}$/', $login)) {
            // 📱 電話番号ログイン（ハイフン除去済み想定）
            $user = User::where('phone', $login)->first();
        } else {
            // どちらでもない形式
            throw ValidationException::withMessages([
                'login' => '正しいメールアドレスまたは電話番号を入力してください。',
            ]);
        }

        // ✅ 認証チェック
        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        // ✅ ログイン処理
        Auth::login($user, $request->boolean('remember'));

        // ✅ ログイン時刻更新
        $user->forceFill(['last_login_at' => now()])->save();

        // ✅ セッション再生成
        $request->session()->regenerate(true);

        // ✅ Inertia対応
        if ($request->header('X-Inertia')) {
            return Inertia::location(route('dashboard'));
        }

        return redirect()->intended(route('dashboard', absolute: false))->setStatusCode(303);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 🚀 フルリロードでCSRFを新規取得
        return Inertia::location(url('/'));
    }
}
