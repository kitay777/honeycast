<?php

// app/Http/Controllers/LineWebhookController.php
namespace App\Http\Controllers;

use App\Models\LineLinkCode;
use App\Models\User;
use App\Services\LineService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class LineWebhookController extends Controller
{
    public function handle(Request $req, LineService $line)
    {
        $raw = $req->getContent();
        $sig = $req->header('X-Line-Signature');

        if (!$line->verifySignature($raw, $sig)) {
            Log::warning('LINE signature invalid');
            return response()->json(['ok'=>false], 400);
        }

        $payload = $req->json()->all();
        foreach (($payload['events'] ?? []) as $ev) {

            $type = $ev['type'] ?? '';
            $sourceUserId = $ev['source']['userId'] ?? null;

            // 友だち追加/ブロック解除など（軽い挨拶）
            if ($type === 'follow' && $sourceUserId) {
                $line->replyText($ev['replyToken'] ?? '', "友だち追加ありがとうございます！\n「連携コード」を送って連携してください。");
            }

            // 連携コードをトークで受信 → 照合 → ユーザーに紐付け
            if ($type === 'message' && ($ev['message']['type'] ?? '') === 'text' && $sourceUserId) {
                $text = trim($ev['message']['text'] ?? '');
                // 6〜12桁の英数のみをコードとして受け付け
                if (preg_match('/^[A-Z0-9]{6,12}$/i', $text)) {
                    $code = strtoupper($text);
                    $rec = LineLinkCode::where('code', $code)
                        ->whereNull('used_at')
                        ->where('expires_at','>', now())
                        ->first();

                    if ($rec) {
                        $user = User::find($rec->user_id);
                        if ($user) {
                            // プロフィール取得（任意）
                            $profile = $line->getProfile($sourceUserId);
                            $user->update([
                                'line_user_id' => $sourceUserId,
                                'line_display_name' => $profile['displayName'] ?? null,
                                'line_opt_in_at' => now(),
                            ]);
                            $rec->update(['used_at'=>now()]);

                            $line->replyText($ev['replyToken'] ?? '', "連携が完了しました！このLINEに通知を送ります。");
                            continue;
                        }
                    }
                    $line->replyText($ev['replyToken'] ?? '', "連携コードが無効か、期限切れです。マイページでコードを再発行してください。");
                }
            }

            // ブロック（unfollow）されたら内部状態を更新したい場合
            if ($type === 'unfollow' && $sourceUserId) {
                User::where('line_user_id', $sourceUserId)
                    ->update(['line_opt_in_at'=>null]); // or nullにする等、運用に合わせて
            }
        }

        return response()->json(['ok'=>true]);
    }
}
