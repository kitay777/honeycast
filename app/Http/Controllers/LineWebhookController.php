<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\CallRequestCast; // 冒頭で import

class LineWebhookController extends Controller
{
    public function handle(Request $request)
    {
        \Log::info('LINE webhook IN', $request->all());
        $token  = config('services.line.channel_access_token');
        $events = $request->input('events', []);

        foreach ($events as $ev) {
            $type = $ev['type'] ?? '';
            $replyToken = $ev['replyToken'] ?? null;
            $lineUserId = $ev['source']['userId'] ?? null;

            /* ★ postback（参加/辞退） */
            if ($type === 'postback') {
                $dataStr = (string)($ev['postback']['data'] ?? '');
                parse_str($dataStr, $p); // assign_id=..&action=accept|decline
                $assignId = (int)($p['assign_id'] ?? 0);
                $action   = ($p['action'] ?? '') === 'accept' ? 'accepted' :
                            (($p['action'] ?? '') === 'decline' ? 'declined' : null);

                if ($assignId && $action && $lineUserId) {
                    $as = CallRequestCast::with('castProfile.user','callRequest')->find($assignId);
                    if ($as && $as->castProfile?->user?->line_user_id === $lineUserId) {
                        // まだ未応答/招待中のみ更新
                        if (!$as->responded_at && in_array($as->status, ['invited','assigned','pending'], true)) {
                            $as->status       = $action;
                            $as->responded_at = now();
                            $as->save();

                            // （任意）リクエスト側の状態更新もここで
                            // 例：1人でも accepted になったら assigned のまま／全員 declined で closed など

                            // 返信
                            if ($replyToken && $token) {
                                $msg = $action === 'accepted'
                                    ? "参加で承りました。ありがとうございます。"
                                    : "辞退で承りました。またの機会にお願いします。";
                                \Http::withToken($token)->post('https://api.line.me/v2/bot/message/reply', [
                                    'replyToken' => $replyToken,
                                    'messages' => [[ 'type'=>'text', 'text'=>$msg ]],
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
                continue; // ← postback はここで完了
            }

            // 既存: follow（案内返信）、message（連携コード）など…
        }

        return response()->json(['ok'=>true]);
    }
}
