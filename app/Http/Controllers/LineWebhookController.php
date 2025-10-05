<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LineWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 受信ログ（デバッグ用）
        \Log::info('LINE webhook IN', $request->all());

        $token  = config('services.line.channel_access_token'); // .env: LINE_CHANNEL_ACCESS_TOKEN
        $events = $request->input('events', []);

        foreach ($events as $ev) {
            if (($ev['type'] ?? '') === 'follow') {
                // フォロー時に案内を返す
                if (!empty($ev['replyToken']) && $token) {
                    \Illuminate\Support\Facades\Http::withToken($token)
                        ->post('https://api.line.me/v2/bot/message/reply', [
                            'replyToken' => $ev['replyToken'],
                            'messages' => [[
                                'type' => 'text',
                                'text' => "通知連携ありがとうございます。\nアプリの『連携コードを発行』で表示された6桁コードを、このトークに送ってください。"
                            ]],
                        ]);
                }
                continue; // follow はここで終わり
            }
            $type = $ev['type'] ?? '';
            if ($type !== 'message') { continue; }

            $msgType = $ev['message']['type'] ?? '';
            if ($msgType !== 'text') { continue; }

            $text       = (string)($ev['message']['text'] ?? '');
            $replyToken = $ev['replyToken'] ?? null;
            $lineUserId = $ev['source']['userId'] ?? null;

            if (!$lineUserId || $text === '') { continue; }

            // --- コード抽出（全角→半角、空白除去、先頭の英数4〜10文字を拾う）---
            $norm = Str::upper(mb_convert_kana($text, 'as')); // 全角→半角 & 大文字
            $norm = preg_replace('/\s+/u', '', $norm);
            // 例： ABC123 ／ ABC-123 ／ 123ABC を拾う
            if (!preg_match('/([A-Z0-9]{4,10})/', $norm, $m)) {
                \Log::warning('LINE webhook: no code pattern', ['text' => $text, 'norm' => $norm]);
                continue;
            }
            $code = $m[1];

            // --- 有効なコードを検索 ---
            $pair = DB::table('line_link_codes')
                ->where('code', $code)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->orderByDesc('id')
                ->first();

            if (!$pair) {
                \Log::warning('LINE webhook: code not found/expired', ['code' => $code]);
                // 必要なら「コードが無効です」と返信
                if ($replyToken && $token) {
                    Http::withToken($token)->post('https://api.line.me/v2/bot/message/reply', [
                        'replyToken' => $replyToken,
                        'messages' => [['type' => 'text', 'text' => 'コードが無効です。もう一度「連携コードを発行」からお試しください。']],
                    ]);
                }
                continue;
            }

            // --- 表示名（任意で取得） ---
            $displayName = null;
            if ($token) {
                $prof = Http::withToken($token)->get("https://api.line.me/v2/bot/profile/{$lineUserId}");
                if ($prof->successful()) { $displayName = $prof->json('displayName'); }
            }

            // --- users を更新 ---
            DB::table('users')->where('id', $pair->user_id)->update([
                'line_user_id'      => $lineUserId,
                'line_display_name' => $displayName,
                'updated_at'        => now(),
            ]);

            // --- コードを使用済みに ---
            DB::table('line_link_codes')->where('id', $pair->id)->update([
                'used_at'    => now(),
                'updated_at' => now(),
            ]);

            \Log::info('LINE webhook LINKED', ['user_id' => $pair->user_id, 'line_user_id' => $lineUserId, 'code' => $code]);

            // --- 成功リプライ（ユーザーに分かるように） ---
            if ($replyToken && $token) {
                Http::withToken($token)->post('https://api.line.me/v2/bot/message/reply', [
                    'replyToken' => $replyToken,
                    'messages' => [['type' => 'text', 'text' => '連携が完了しました。通知を受け取れるようになりました。']],
                ]);
            }
        }

        return response()->json(['ok' => true]);
    }
}
