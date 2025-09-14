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
        $request->validate([
            'name' => 'required|string|max:255',
            'area'                  => ['nullable', 'string', 'max:255'],
            'phone'                 => ['nullable', 'string', 'max:30'],
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', Rules\Password::defaults()],
        ]);
        $shopId = null; $inviteId = null;
        if ($token = $request->session()->pull('shop_token')) {
            $invite = ShopInvite::where('token', $token)->first();
            if ($invite && $invite->isValid()) {
                $shopId  = $invite->shop_id;
                $inviteId = $invite->id;
                $invite->increment('used_count');
            }
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'shop_id' => $shopId,    // ← 追加
        ]);

        if ($inviteId) {
            ShopInviteUsage::firstOrCreate([
                'shop_invite_id' => $inviteId,
                'user_id'        => $user->id,
            ], [
                'ip'         => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        }
        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
