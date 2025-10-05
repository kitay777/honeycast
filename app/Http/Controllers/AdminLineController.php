<?php

// app/Http/Controllers/AdminLineController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class AdminLineController extends Controller
{
    public function push(Request $request, User $user)
    {
        $data = $request->validate([
            'text' => ['required','string','max:1000'], // 適宜
        ]);

        if (!$user->line_user_id) {
            return back()->with('error', 'このユーザーはLINE未連携です。');
        }

        $token = config('services.line.channel_access_token');
        if (!$token) {
            return back()->with('error', 'LINE_CHANNEL_ACCESS_TOKEN が未設定です。');
        }

        $payload = [
            'to' => $user->line_user_id,
            'messages' => [
                ['type' => 'text', 'text' => $data['text']],
            ],
        ];

        $res = Http::withToken($token)
            ->asJson()
            ->post('https://api.line.me/v2/bot/message/push', $payload);

        if ($res->successful()) {
            return back()->with('success', '送信しました。');
        }

        // エラー内容を見たいとき
        return back()->with('error', "送信失敗 ({$res->status()}): ".$res->body());
    }
}
