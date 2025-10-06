<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CastProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class LineRegistrationController extends Controller
{
// app/Http/Controllers/LineRegistrationController.php

public function completeWithToken(Request $request)
{
    $t = (string) $request->query('t', '');
    if ($t === '') {
        \Log::warning('LINE complete: token missing');
        return redirect()->route('line.register')->with('error', 'リンクが不正です。');
    }

    $row = \DB::table('line_follow_tokens')->where('token', $t)->first();
    if (!$row) {
        \Log::warning('LINE complete: token not found', ['t'=>$t]);
        return redirect()->route('line.register')->with('error', 'リンクが無効です。');
    }
    if ($row->used_at) {
        \Log::warning('LINE complete: token used', ['t'=>$t]);
        return redirect()->route('line.register')->with('error', 'このリンクは既に使用されています。');
    }
    if (now()->greaterThan($row->expires_at)) {
        \Log::warning('LINE complete: token expired', ['t'=>$t, 'exp'=>$row->expires_at]);
        return redirect()->route('line.register')->with('error', 'このリンクは期限切れです。');
    }

    // ★ 未ログインならトークンをセッションに退避してログインへ
    if (!\Auth::check()) {
        $request->session()->put('line_follow_token', $t);
        return redirect()->route('login')->with('info', 'ログインするとLINE連携が完了します。');
    }

    // ★ ここから：ログイン中の“今のユーザー”にだけ紐づけ（自動作成しない）
    $user       = $request->user();
    $lineUserId = $row->line_user_id;

    // 表示名（任意）
    $display = null;
    $access  = config('services.line.channel_access_token');
    if ($access) {
        $prof = \Http::withToken($access)->get("https://api.line.me/v2/bot/profile/{$lineUserId}");
        if ($prof->successful()) $display = $prof->json('displayName');
    }

    \DB::transaction(function () use ($user, $row, $lineUserId, $display) {
        // 付け替え：同じLINEが他ユーザーに付いていたら外す
        \DB::table('users')->where('line_user_id', $lineUserId)
            ->where('id', '!=', $user->id)
            ->update(['line_user_id'=>null,'line_display_name'=>null,'updated_at'=>now()]);

        $user->forceFill([
            'line_user_id'      => $lineUserId,
            'line_display_name' => $display,
        ])->save();

        // トークン消費
        \DB::table('line_follow_tokens')->where('id', $row->id)->update([
            'used_at'=>now(),'updated_at'=>now(),
        ]);
    });

    return redirect()->route('cast.profile.edit')->with('success','LINE連携が完了しました。');
}

    /** InertiaでLIFFページを返す */
    public function page(Request $request)
    {
        return Inertia::render('Auth/LineRegister', [
            'liffId'   => config('services.line.liff_id'),
            'botUrl'   => config('services.line.bot_add_url'),
            'redirect' => $request->query('redirect', route('cast.profile.edit', [], false) ?: '/cast/profile/edit'),
        ]);
    }

    /** LIFFからの登録＆ログイン */
    public function direct(Request $request)
    {
        $data = $request->validate([
            'uid'         => ['required','regex:/^U[0-9a-f]{32}$/i'],
            'displayName' => ['nullable','string','max:100'],
            'redirect'    => ['nullable','string','max:2000'],
        ]);

        // ボットで実在プロフィール確認（友だち状態も含む）
        $token = config('services.line.channel_access_token');
        $displayName = $data['displayName'] ?? null;
        if ($token) {
            $prof = Http::withToken($token)->get("https://api.line.me/v2/bot/profile/{$data['uid']}");
            if ($prof->successful()) {
                $displayName = $displayName ?: $prof->json('displayName');
            } else {
                return response()->json(['ok'=>false,'error'=>'not_friend_or_invalid_user'], 400);
            }
        }

        // 既存ならログイン、無ければ作成
        $user = User::where('line_user_id', $data['uid'])->first();
        if (!$user) {
            $user = new User();
            $user->name               = $displayName ?: 'LINEユーザー';
            $user->email              = 'line_'.strtolower($data['uid']).'@example.invalid';
            $user->password           = bcrypt(Str::random(32));
            $user->line_user_id       = $data['uid'];
            $user->line_display_name  = $displayName;
            $user->save();

            // 任意：キャストの雛形も作る
            CastProfile::firstOrCreate(['user_id' => $user->id], ['nickname' => $user->name]);
        } else {
            if ($displayName && $displayName !== $user->line_display_name) {
                $user->line_display_name = $displayName;
                $user->save();
            }
        }

        Auth::login($user, true);

        return response()->json([
            'ok'       => true,
            'redirect' => $data['redirect'] ?? (route('cast.profile.edit', [], false) ?: '/cast/profile/edit'),
        ]);
    }
}
