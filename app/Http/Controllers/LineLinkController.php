<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;

use App\Models\CastProfile;

class LineLinkController extends Controller
{
    public function direct(Request $request)
        {
            
            $data = $request->validate([
                'uid'         => ['required','regex:/^U[0-9a-f]{32}$/i'],
                'displayName' => ['nullable','string','max:100'],
            ]);
            \Log::info('LINK_DIRECT_IN', ['uid'=>$data['uid'], 'user_id'=>$request->user()->id]);

            $user  = $request->user();
            $token = config('services.line.channel_access_token');

            // 友だち状態/有効性の確認（friend でないと失敗する）
            $displayName = $data['displayName'] ?? null;
            if ($token) {
                $prof = Http::withToken($token)->get("https://api.line.me/v2/bot/profile/{$data['uid']}");
                if ($prof->successful()) {
                    $displayName = $displayName ?: $prof->json('displayName');
                } else {
                    return response()->json(['ok'=>false,'error'=>'not_friend_or_invalid_user'], 400);
                }
            }

            // すでに他ユーザーにひも付いている場合は外す or 拒否（ここは外す実装）
            \DB::table('users')->where('line_user_id', $data['uid'])
                ->where('id', '!=', $user->id)
                ->update(['line_user_id'=>null, 'line_display_name'=>null, 'updated_at'=>now()]);

            $user->forceFill([
                'line_user_id'      => $data['uid'],
                'line_display_name' => $displayName,
                'line_opt_in_at'    => now(),
            ])->save();
            \Log::info('LINK_DIRECT_SAVED', ['user_id'=>$user->id, 'line_user_id'=>$user->fresh()->line_user_id]);
            return response()->json(['ok'=>true, 'displayName'=>$displayName, 'uid'=>$data['uid']]);
        }
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
    $u = $request->user()->fresh();
    $payload = [
        'linked'        => (bool) $u->line_user_id,
        'user_id'       => $u->line_user_id,
        'display_name'  => $u->line_display_name,
    ];

    // JSON要求ならJSONで返す（fetch/poll用）
    if ($request->wantsJson() || $request->query('json')) {
        return response()->json($payload);
    }

    // 互換：Inertia へ flash で返す
    return back()->with('line_status', $payload);
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
