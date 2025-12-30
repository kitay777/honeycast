<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\CastRegisterRequest;
use App\Models\CastProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class CastRegisterController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/CastRegister');
    }

    public function store(CastRegisterRequest $request)
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $request) {
            // 1) User 作成
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'area'     => $data['area'] ?? null, // usersにareaが無いなら外してOK
                'password' => Hash::make($data['password']),
                'is_cast'  => true,
            ]);

            // 2) 画像アップロード
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('cast_photos', 'public');
            }

            // 3) CastProfile 作成（デフォルトはブラーON）
            CastProfile::create([
                'user_id'   => $user->id,

                // ★ 必須（null禁止）
                'nickname'  => $data['nickname'],
                'age'       => $data['age'],
                'height_cm' => $data['height_cm'],

                // 任意
                'rank'      => $data['rank'] ?? null,
                'cup'       => $data['cup'] ?? null,
                'style'     => $data['style'] ?? null,
                'alcohol'   => $data['alcohol'] ?? null,
                'mbti'      => isset($data['mbti']) ? strtoupper($data['mbti']) : null,
                'area'      => $data['cast_area'] ?? ($data['area'] ?? null),
                'tags'      => $data['tags'] ?? [],
                'freeword'  => $data['freeword'] ?? null,

                'photo_path'      => $photoPath,
                'is_blur_default' => true,
            ]);

            return $user;
        });

        // Breeze標準：ログイン＆登録イベント（メール認証有効時は通知）
        Auth::login($user);
        event(new Registered($user));

        // メール認証を使っている場合
        if (config('auth.verification')) {
            return redirect()->route('verification.notice');
        }

        // 認証なしならマイページへ
        return redirect()->route('cast.profile.edit')->with('success', 'キャスト登録が完了しました。');
    }

    // 既存ユーザー → キャスト化（任意）
    public function upgrade(Request $request)
    {
        $user = $request->user();

        if ($user->castProfile) {
            return back()->with('info', 'すでにキャストプロフィールが存在します。');
        }

        $profile = CastProfile::create([
            'user_id'         => $user->id,
            'is_blur_default' => true,
        ]);

        if (!$user->is_cast) {
            $user->forceFill(['is_cast' => true])->save();
        }
        return redirect()->route('cast.profile.edit')->with('success', 'キャストプロフィールを作成しました。');
    }
}
