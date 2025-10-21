<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;



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
    $request->validate([
        'email'    => ['required', 'string', 'email'],
        'password' => ['required', 'string'],
    ]);

    $email = mb_strtolower(trim((string)$request->input('email')));
    $password = (string)$request->input('password');

    $user = \App\Models\User::where('email', $email)->first();
    if (!$user || !\Illuminate\Support\Facades\Hash::check($password, $user->password)) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    \Illuminate\Support\Facades\Auth::login($user, $request->boolean('remember'));
    $request->session()->regenerate(true);

    // ★ ここがポイント：Inertiaリクエストなら「確実な遷移」を指示
    if ($request->header('X-Inertia')) {
        return Inertia::location(route('dashboard')); // ← ブラウザにハードリダイレクトを指示
    }

    // 通常のフォームでもOKなようにフォールバック
    return redirect()->intended(route('dashboard', absolute:false))->setStatusCode(303);
}

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
