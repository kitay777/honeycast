<?php
// app/Http/Controllers/AdminLineController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class AdminLineController extends Controller
{
    private const PUSH_ENDPOINT       = 'https://api.line.me/v2/bot/message/push';
    private const MULTICAST_ENDPOINT  = 'https://api.line.me/v2/bot/message/multicast';

    /** フォーム表示（個別） */
    public function form(User $user)
    {
        return view('admin.line', [
            'user' => $user,
            'tokenExists' => (bool) config('services.line.channel_access_token'),
        ]);
    }

    /** 個別送信 */
    public function push(Request $request, User $user)
    {
        $data = $request->validate([
            'text' => ['required','string','max:1000'],
            'notification_disabled' => ['sometimes','boolean'],
        ]);

        if (!$user->line_user_id) {
            return back()->with('error', 'このユーザーはLINE未連携です。');
        }

        $token = config('services.line.channel_access_token');
        if (!$token) {
            return back()->with('error', 'LINE_CHANNEL_ACCESS_TOKEN が未設定です。');
        }

        // 改行/空白整形（CRLF→LF、前後trim）
        $text = preg_replace("/\r\n?/", "\n", trim($data['text']));

        $payload = [
            'to' => $user->line_user_id,
            'messages' => [[ 'type' => 'text', 'text' => $text ]],
        ];
        if (!empty($data['notification_disabled'])) {
            $payload['notificationDisabled'] = true;
        }

        try {
            $res = Http::withToken($token)
                ->asJson()
                ->post(self::PUSH_ENDPOINT, $payload);

            if ($res->successful()) {
                Log::info('LINE push ok', ['to'=>$user->id, 'len'=>mb_strlen($text)]);
                return back()->with('success', '送信しました。');
            }

            // 429の簡易リトライ案内
            $retryAfter = $res->header('Retry-After');
            $hint = $retryAfter ? " / Retry-After: {$retryAfter}s" : '';
            Log::warning('LINE push failed', ['status'=>$res->status(), 'body'=>$res->body(), 'to'=>$user->id]);

            return back()->with('error', "送信失敗 ({$res->status()}): ".$res->body().$hint);
        } catch (ConnectionException $e) {
            Log::error('LINE push connection error', ['ex'=>$e->getMessage()]);
            return back()->with('error', '送信失敗: ネットワークエラー');
        }
    }

    /** 一括送信（任意）: 選択 user_id[] 宛に multicast（500件/回まで） */
    public function multicast(Request $request)
    {
        $data = $request->validate([
            'user_ids'   => ['required','array','min:1'],
            'user_ids.*' => ['integer','exists:users,id'],
            'text'       => ['required','string','max:1000'],
            'notification_disabled' => ['sometimes','boolean'],
        ]);

        $token = config('services.line.channel_access_token');
        if (!$token) {
            return back()->with('error', 'LINE_CHANNEL_ACCESS_TOKEN が未設定です。');
        }

        $to = User::whereIn('id', $data['user_ids'])
            ->whereNotNull('line_user_id')
            ->pluck('line_user_id')
            ->values()
            ->all();

        if (empty($to)) {
            return back()->with('error', 'LINE連携済みユーザーが選択されていません。');
        }

        $text = preg_replace("/\r\n?/", "\n", trim($data['text']));
        $chunks = array_chunk($to, 500);
        $errors = [];

        foreach ($chunks as $i => $chunk) {
            $payload = [
                'to' => $chunk,
                'messages' => [[ 'type' => 'text', 'text' => $text ]],
            ];
            if (!empty($data['notification_disabled'])) {
                $payload['notificationDisabled'] = true;
            }

            try {
                $res = Http::withToken($token)->asJson()->post(self::MULTICAST_ENDPOINT, $payload);
                if (!$res->successful()) {
                    $errors[] = "chunk#{$i} ({$res->status()}): ".$res->body();
                    Log::warning('LINE multicast failed', ['chunk'=>$i, 'status'=>$res->status(), 'body'=>$res->body()]);
                }
            } catch (ConnectionException $e) {
                $errors[] = "chunk#{$i} network error: ".$e->getMessage();
                Log::error('LINE multicast connection error', ['chunk'=>$i, 'ex'=>$e->getMessage()]);
            }
        }

        return empty($errors)
            ? back()->with('success', '一括送信しました。')
            : back()->with('error', implode("\n", $errors));
    }
}
