<?php

namespace App\Jobs;

use App\Models\CallMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendMatchEndReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $matchId;

    public function __construct($matchId)
    {
        $this->matchId = $matchId;
    }

    public function handle()
    {
        $match = CallMatch::with(['castProfile.user', 'callRequest.user'])->find($this->matchId);
        if (!$match) return;

        $token = config('services.line.channel_access_token');

        $castUserId = $match->castProfile->user->line_user_id ?? null;
        $clientUserId = $match->callRequest->user->line_user_id ?? null;

        $endAt = $match->started_at->copy()->addMinutes($match->duration_minutes)->format('H:i');

        // 💬 共通メッセージ
        $msgBase = "⏰【あと10分で終了】\n"
            . "キャスト：{$match->castProfile->nickname}\n"
            . "終了予定：{$endAt}";

        // 🎭 キャスト向け通知
        if ($castUserId) {
            Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', [
                'to' => $castUserId,
                'messages' => [
                    ['type' => 'text', 'text' => "{$msgBase}\n\nお疲れ様です。まもなく終了です。"]
                ]
            ]);
        }

        // 👤 ユーザー向け通知（ボタン付き）
        if ($clientUserId) {
            $body = [
                'to' => $clientUserId,
                'messages' => [[
                    'type' => 'template',
                    'altText' => 'マッチ終了10分前です。延長しますか？',
                    'template' => [
                        'type' => 'buttons',
                        'title' => 'マッチ終了10分前です',
                        'text' => '延長をご希望の場合は下記を選択してください',
                        'actions' => [
                            [
                                'type' => 'postback',
                                'label' => '＋1時間延長',
                                'data' => "action=extend&hours=1&match_id={$match->id}"
                            ],
                            [
                                'type' => 'postback',
                                'label' => '＋2時間延長',
                                'data' => "action=extend&hours=2&match_id={$match->id}"
                            ],
                        ]
                    ]
                ]]
            ];
            Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', $body);
        }

        Log::info("LINE通知: マッチ{$match->id} 終了10分前リマインダー送信");
    }
}
