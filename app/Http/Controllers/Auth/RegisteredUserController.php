<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\ShopInvite;
use App\Models\ShopInviteUsage;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // ✅ メール or 電話のどちらか必須
        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'area'  => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'regex:/^[0-9]{10,11}$/', 'unique:users,phone'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if (empty($request->email) && empty($request->phone)) {
            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたは電話番号のいずれかを入力してください。',
                'phone' => 'メールアドレスまたは電話番号のいずれかを入力してください。',
            ]);
        }

        // ✅ 招待トークン処理（既存ロジック維持）
        $shopId = null;
        $inviteId = null;

        if ($token = $request->session()->pull('shop_token')) {
            $invite = ShopInvite::where('token', $token)->first();
            if ($invite && $invite->isValid()) {
                $shopId  = $invite->shop_id;
                $inviteId = $invite->id;
                $invite->increment('used_count');
            }
        }

        // ✅ ユーザー登録
        $user = User::create([
            'name'     => $request->name,
            'area'     => $request->area,
            'phone'    => $request->phone ? preg_replace('/[^0-9]/', '', $request->phone) : null,
            'email'    => $request->email ? mb_strtolower(trim($request->email)) : null,
            'password' => Hash::make($request->password),
            'shop_id'  => $shopId,
        ]);

        // ✅ 招待利用履歴
        if ($inviteId) {
            ShopInviteUsage::firstOrCreate([
                'shop_invite_id' => $inviteId,
                'user_id'        => $user->id,
            ], [
                'ip'         => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        }

        // ✅ イベント発火＆ログイン
        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
