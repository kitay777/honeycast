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
    /** 管理者がユーザーにLINEメッセージを送信 */
    public function push(Request $request, User $user)
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:1000'],
            'notification_disabled' => ['boolean'],
        ]);

        // ✅ LINEユーザーIDを取得（usersテーブルに line_user_id カラムがある前提）
        if (!$user->line_user_id) {
            return back()->with('error', 'このユーザーはLINE連携がされていません。');
        }

        try {
            $token = config('services.line.channel_access_token');

            // ✅ LINE Bot へ送信
            Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', [
                'to' => $user->line_user_id,
                'messages' => [[
                    'type' => 'text',
                    'text' => $data['text'],
                ]],
                'notificationDisabled' => (bool)($data['notification_disabled'] ?? false),
            ]);

            return back()->with('success', 'LINEメッセージを送信しました。');
        } catch (\Throwable $e) {
            Log::error('LINE送信エラー: '.$e->getMessage());
            return back()->with('error', 'LINE送信に失敗しました。');
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
