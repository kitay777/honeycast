<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\CallRequestCast;

class LineWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 受信ログ（確認用）
        \Log::info('LINE webhook IN', $request->all());

        $token  = config('services.line.channel_access_token');
        $events = $request->input('events', []);

        foreach ($events as $ev) {
            $type       = $ev['type'] ?? '';
            $replyToken = $ev['replyToken'] ?? null;
            $lineUserId = $ev['source']['userId'] ?? null;

            /* ===================== 1) 参加/辞退（postback） ===================== */
if ($type === 'postback') {
    $dataStr = (string)($ev['postback']['data'] ?? '');
    parse_str($dataStr, $p);
    $assignId = (int)($p['assign_id'] ?? 0);
    $action   = ($p['action'] ?? '') === 'accept' ? 'accepted'
              : (($p['action'] ?? '') === 'decline' ? 'declined' : null);

    if ($assignId && $action && $lineUserId) {
        $as = CallRequestCast::with('castProfile.user', 'callRequest')->find($assignId);

        if ($as && $as->castProfile?->user?->line_user_id === $lineUserId) {
            if (!$as->responded_at && in_array($as->status, ['invited','assigned','pending'], true)) {
                $as->status       = $action;
                $as->responded_at = now();
                $as->save();

                // ✅ 管理者通知
                $call = $as->callRequest;
                $adminId = env('LINE_ADMIN_USER_ID');
                if ($adminId && $token) {
                    $msg = "📢 【{$as->castProfile->nickname}】さんがリクエスト #{$call->id} に「"
                        . ($action === 'accepted' ? '参加' : '辞退')
                        . "」しました。\n"
                        . "📅 {$call->date} {$call->start_time}〜{$call->end_time}\n"
                        . "📍 {$call->place}\n";
                    Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', [
                        'to' => $adminId,
                        'messages' => [[ 'type' => 'text', 'text' => $msg ]],
                    ]);
                }

                // ✅ キャストへの返信
                if ($replyToken && $token) {
                    $msg = $action === 'accepted'
                        ? "参加で承りました。ありがとうございます。"
                        : "辞退で承りました。またの機会にお願いします。";
                    Http::withToken($token)->post('https://api.line.me/v2/bot/message/reply', [
                        'replyToken' => $replyToken,
                        'messages'   => [[ 'type'=>'text', 'text'=>$msg ]],
                    ]);
                }

                \Log::info('LINE postback handled', ['assign_id'=>$assignId, 'action'=>$action]);
            } else {
                \Log::info('LINE postback ignored: already responded or status fixed', ['assign_id'=>$assignId]);
            }
        } else {
            \Log::warning('LINE postback invalid user', ['assign_id'=>$assignId, 'line_user_id'=>$lineUserId]);
        }
    } else {
        \Log::warning('LINE postback malformed', ['data'=>$dataStr]);
    }

    continue; // postback はここで完了
}


            /* ===================== 2) 友だち追加（follow） ===================== */
if ($type === 'follow') {
    if (!$lineUserId) continue;

    // 1) ワンタイムトークン発行（10分有効）
    $followToken = Str::random(40);
    DB::table('line_follow_tokens')->insert([
        'token'        => $followToken,
        'line_user_id' => $lineUserId,
        'expires_at'   => now()->addMinutes(10),
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    // 2) ユーザーに「1タップで登録/連携」リンクを送る
    $link = url('/register/line/complete?t=' . $followToken);

    if ($replyToken && $token) {
        Http::withToken($token)->post('https://api.line.me/v2/bot/message/reply', [
            'replyToken' => $replyToken,
            'messages'   => [[
                'type' => 'text',
                // 文言はお好みで
                'text' => "友だち追加ありがとうございます。\n下のリンクをタップすると登録/連携が自動で完了します。\n{$link}",
            ]],
        ]);
    }
    continue;
}

            /* ===================== 3) メッセージ（連携コード処理） ===================== */
            if ($type === 'message' && ($ev['message']['type'] ?? '') === 'text') {
                $text = (string)($ev['message']['text'] ?? '');
                if (!$lineUserId || $text === '') continue;

                // 全角→半角・大文字化・空白除去 → 英数4〜10文字を抽出
                $norm = Str::upper(mb_convert_kana($text, 'as'));
                $norm = preg_replace('/\s+/u', '', $norm);
                if (!preg_match('/([A-Z0-9]{4,10})/', $norm, $m)) continue;
                $code = $m[1];

                // 有効コード検索
                $pair = DB::table('line_link_codes')
                    ->where('code', $code)
                    ->whereNull('used_at')
                    ->where('expires_at', '>', now())
                    ->orderByDesc('id')
                    ->first();

                if (!$pair) {
                    \Log::warning('LINE webhook: code not found/expired', ['code' => $code]);
                    if ($replyToken && $token) {
                        Http::withToken($token)->post('https://api.line.me/v2/bot/message/reply', [
                            'replyToken' => $replyToken,
                            'messages'   => [[ 'type'=>'text', 'text'=>'コードが無効です。もう一度「連携コードを発行」からお試しください。' ]],
                        ]);
                    }
                    continue;
                }

                // 表示名（任意）
                $displayName = null;
                if ($token) {
                    $prof = Http::withToken($token)->get("https://api.line.me/v2/bot/profile/{$lineUserId}");
                    if ($prof->successful()) $displayName = $prof->json('displayName');
                }

                // 保存（付け替え可：同一 userId の他ユーザーからは外す）
                DB::transaction(function () use ($pair, $lineUserId, $displayName) {
                    // 付け替えポリシー：他ユーザーに同じ LINE が付いていたら外す
                    DB::table('users')
                        ->where('line_user_id', $lineUserId)
                        ->where('id', '!=', $pair->user_id)
                        ->update([
                            'line_user_id'      => null,
                            'line_display_name' => null,
                            'updated_at'        => now(),
                        ]);

                    // コードの user_id に紐づけ
                    DB::table('users')->where('id', $pair->user_id)->update([
                        'line_user_id'      => $lineUserId,
                        'line_display_name' => $displayName,
                        'updated_at'        => now(),
                    ]);

                    // コード消費
                    DB::table('line_link_codes')->where('id', $pair->id)->update([
                        'used_at'    => now(),
                        'updated_at' => now(),
                    ]);
                });

                \Log::info('LINE webhook LINKED', ['user_id' => $pair->user_id, 'line_user_id' => $lineUserId, 'code' => $code]);

                // 成功返信
                if ($replyToken && $token) {
                    Http::withToken($token)->post('https://api.line.me/v2/bot/message/reply', [
                        'replyToken' => $replyToken,
                        'messages'   => [[ 'type'=>'text', 'text'=>'連携が完了しました。通知を受け取れるようになりました。' ]],
                    ]);
                }

                continue;
            }

            // そのほかのイベントは無視（unfollow等）
        }

        return response()->json(['ok' => true]);
    }
}
