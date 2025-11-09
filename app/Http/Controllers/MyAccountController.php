<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MyAccountController extends Controller
{
    /** 編集ページ表示 */
    public function edit(): Response
    {
        return Inertia::render('MyPage/AccountEdit', [
            'user' => Auth::user(),
        ]);
    }

    /** 更新処理 */
    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'regex:/^[0-9]{10,11}$/', 'unique:users,phone,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        // すべて空ならエラー
        if (empty($data['email']) && empty($data['phone']) && empty($data['password'])) {
            throw ValidationException::withMessages([
                'email' => '変更する項目を入力してください。',
            ]);
        }

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if (!empty($data['email'])) {
            $user->email = mb_strtolower(trim($data['email']));
        }

        if (!empty($data['phone'])) {
            $user->phone = preg_replace('/[^0-9]/', '', $data['phone']);
        }

        $user->save();

        return back()->with('success', 'アカウント情報を更新しました。');
    }
}
