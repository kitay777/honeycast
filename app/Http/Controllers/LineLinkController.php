<?php

// app/Http/Controllers/LineLinkController.php
namespace App\Http\Controllers;

use App\Models\LineLinkCode;
use App\Services\LineService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LineLinkController extends Controller
{
    public function start(Request $req)
    {
        $user = $req->user();
        // 既存の未使用コードを無効化（任意）
        LineLinkCode::where('user_id',$user->id)->whereNull('used_at')->delete();

        $code = Str::upper(Str::random(6));
        $rec = LineLinkCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(15),
        ]);

        return back()->with('line', [
            'code' => $rec->code,
            'bot_url' => config('services.line.friend_url'),
            'bot_qr'  => config('services.line.friend_qr'),
        ]);
    }

    public function status(Request $req)
    {
        $u = $req->user()->fresh();
        return back()->with('line_status', [
            'linked' => (bool)$u->line_user_id,
            'user_id' => $u->line_user_id,
            'display_name' => $u->line_display_name,
        ]);
    }

    public function pushTest(Request $req, LineService $line)
    {
        $u = $req->user();
        if (!$u->line_user_id) {
            return back()->with('flash', ['error' => 'LINE未連携です。']);
        }
        $line->pushText($u->line_user_id, 'テスト通知：連携ありがとうございます！');
        return back()->with('flash', ['success' => 'テスト通知を送信しました。']);
    }

    public function disconnect(Request $req)
    {
        $u = $req->user();
        $u->update([
            'line_user_id' => null,
            'line_display_name' => null,
            'line_opt_in_at' => null,
        ]);
        // 古い未使用コードは削除（任意）
        LineLinkCode::where('user_id', $u->id)->delete();
        return back()->with('flash', ['success'=>'LINE連携を解除しました。']);
    }
}
