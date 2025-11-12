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
        'phone'   => 'required|string|max:20', // ✅ 追加
        'message' => 'required|string|max:2000',
    ]);

    $this->notifyLine($data);

    return back()->with('success', 'お問い合わせを送信しました。');
}

protected function notifyLine(array $data)
{
    $token = config('services.line.channel_access_token');
    $toUserId = config('services.line.admin_user_id');

    if (!$token || !$toUserId) {
        \Log::warning('LINE通知失敗: 設定不足');
        return;
    }

    $text = "📩【お問い合わせ】\n"
          . "お名前：{$data['name']}\n"
          . "📧 メール：{$data['email']}\n"
          . "📞 電話番号：{$data['phone']}\n"   // ✅ 追加
          . "💬 内容：\n{$data['message']}";

    $res = \Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', [
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
