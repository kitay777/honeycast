<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LineController extends Controller
{
    /**
     * ✅ LINEログインコールバック
     * LINE Developers で設定した redirect_uri がここに来る
     */
    public function callback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state');

        if (!$code) {
            return redirect('/login')->with('error', 'LINEログインに失敗しました。');
        }

        // --- トークン取得 ---
        $response = Http::asForm()->post('https://api.line.me/oauth2/v2.1/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('services.line.redirect'),
            'client_id' => config('services.line.channel_id'),
            'client_secret' => config('services.line.channel_secret'),
        ]);

        $json = $response->json();
        if (!$response->ok() || empty($json['access_token'])) {
            return redirect('/login')->with('error', 'LINE連携トークンの取得に失敗しました。');
        }

        $accessToken = $json['access_token'];

        // --- プロフィール取得 ---
        $profile = Http::withToken($accessToken)->get('https://api.line.me/v2/profile')->json();
        if (empty($profile['userId'])) {
            return redirect('/login')->with('error', 'LINEプロフィールの取得に失敗しました。');
        }

        // --- ログイン or 登録処理 ---
        $lineId = $profile['userId'];
        $displayName = $profile['displayName'] ?? null;

        $user = User::where('line_user_id', $lineId)->first();

        if (!$user && Auth::check()) {
            // 既存ログインユーザー → LINE連携
            $user = Auth::user();
            $user->line_user_id = $lineId;
            $user->line_display_name = $displayName;
            $user->save();
        } elseif (!$user) {
            // 未登録 → 新規登録（メール不要）
            $user = User::create([
                'name' => $displayName ?? 'LINEユーザー',
                'email' => "line_{$lineId}@example.com",
                'password' => bcrypt(str()->random(12)),
                'line_user_id' => $lineId,
                'line_display_name' => $displayName,
            ]);
        }

        Auth::login($user, true);

        // --- キャスト編集ページへリダイレクト ---
        return redirect('/cast/profile/edit')->with('status', 'LINE連携が完了しました。');
    }

    /**
     * ✅ LINE連携解除（任意）
     */
    public function unlink(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();
        $user->line_user_id = null;
        $user->line_display_name = null;
        $user->save();

        return redirect('/cast/profile/edit')->with('status', 'LINE連携を解除しました。');
    }

    public function webhook(Request $request)
{
    $events = $request->input('events', []);
    $token = config('services.line.channel_access_token');

    foreach ($events as $event) {
        if (($event['type'] ?? '') === 'postback') {
            parse_str($event['postback']['data'] ?? '', $data);

            if (($data['action'] ?? '') === 'extend') {
                $match = \App\Models\CallMatch::find($data['match_id'] ?? null);
                if ($match) {
                    $hours = (int)($data['hours'] ?? 1);
                    $match->increment('duration_minutes', $hours * 60);

                    // キャストにも延長通知
                    $castLineId = $match->castProfile->user->line_user_id ?? null;
                    if ($castLineId) {
                        Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', [
                            'to' => $castLineId,
                            'messages' => [['type' => 'text', 'text' => "⏱ マッチが＋{$hours}時間延長されました。"]],
                        ]);
                    }

                    // ユーザーに返信
                    $replyToken = $event['replyToken'];
                    Http::withToken($token)->post('https://api.line.me/v2/bot/message/reply', [
                        'replyToken' => $replyToken,
                        'messages' => [['type' => 'text', 'text' => "✅ {$hours}時間延長しました。"]],
                    ]);
                }
            }
        }
    }

    return response()->json(['status' => 'ok']);
}

}
