<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LineLinkController extends Controller
{
    // app/Http/Controllers/LineLinkController.php
    public function peek(Request $request)
    {
        $row = \DB::table('line_link_codes')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'code'    => $row->code ?? null,
            'bot_url' => config('services.line.bot_add_url'),
            'bot_qr'  => config('services.line.bot_qr'),
        ]);
    }

    public function start(Request $request)
    {
        $user = $request->user();
        $code = Str::upper(Str::random(6));

        // 古い未使用コード掃除
        DB::table('line_link_codes')
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        DB::table('line_link_codes')->insert([
            'user_id'    => $user->id,
            'code'       => $code,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('line', [
            'code'    => $code,
            'bot_url' => config('services.line.bot_add_url'),
            'bot_qr'  => config('services.line.bot_qr'),
        ]);
    }

    public function status(Request $request)
    {
        $u = $request->user();

        return back()->with('line_status', [
            'linked'       => filled($u->line_user_id),
            'user_id'      => $u->line_user_id,
            'display_name' => $u->line_display_name,
        ]);
    }

    public function disconnect(Request $request)
    {
        $u = $request->user();
        $u->forceFill(['line_user_id'=>null, 'line_display_name'=>null])->save();

        return back()->with('success', 'LINE連携を解除しました');
    }

    public function pushTest(Request $request)
    {
        $u = $request->user();
        if (!$u?->line_user_id) {
            return back()->with('error', 'LINE未連携のため送信できません');
        }

        $token = config('services.line.channel_access_token');
        if (!$token) {
            return back()->with('error', 'LINE_CHANNEL_ACCESS_TOKEN が未設定です');
        }

        $payload = [
            'to' => $u->line_user_id,
            'messages' => [['type'=>'text', 'text'=>'テスト通知です']],
        ];

        $res = Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', $payload);

        return $res->successful()
            ? back()->with('success', 'テスト通知を送信しました')
            : back()->with('error', "送信失敗 ({$res->status()}): ".$res->body());
    }
}
