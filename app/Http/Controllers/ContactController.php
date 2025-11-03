<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class ContactController extends Controller
{
    public function index()
    {
        return Inertia::render('Contact/Form', [
            'supportEmail' => 'kitayama@main.co.jp',
            'message' => 'お問い合わせフォーム',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'email'   => 'required|email|max:255',
            'message' => 'required|string|max:2000',
        ]);

        $this->notifyLine($data);

        return back()->with('success', 'お問い合わせを送信しました。');
    }

    protected function notifyLine(array $data)
    {
        // ✅ 管理者をメールで特定して取得
        $admin = User::where('email', 'kitayama@main.co.jp')->first();

        if (!$admin) {
            \Log::warning('LINE通知失敗: 管理者ユーザーが見つかりません。');
            return;
        }

        $toUserId = $admin->line_user_id;
        if (!$toUserId) {
            \Log::warning("LINE通知失敗: {$admin->email} に line_user_id が設定されていません。");
            return;
        }

        // ✅ Messaging API アクセストークン
        $token = config('services.line.channel_access_token');
        if (!$token) {
            \Log::warning('LINE通知失敗: LINEトークンが設定されていません。');
            return;
        }

        // ✅ 通知内容
        $text = "📩【お問い合わせ】\n"
              . "お名前：{$data['name']}\n"
              . "メール：{$data['email']}\n"
              . "内容：\n{$data['message']}";

        // ✅ 送信
        $res = Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', [
            'to' => $toUserId,
            'messages' => [
                ['type' => 'text', 'text' => $text],
            ],
        ]);

        if ($res->failed()) {
            \Log::error('LINE通知エラー: ' . $res->body());
        }
    }
}
