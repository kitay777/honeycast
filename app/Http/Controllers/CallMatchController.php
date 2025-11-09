<?php

namespace App\Http\Controllers;

use App\Models\CallMatch;
use App\Models\CallRequestCast;
use App\Models\CastProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CallMatchController extends Controller
{
    /** 開始ページ（一覧＋開始UI） */
public function showStartPage()
{
    $cast = auth()->user()->castProfile;
    if (!$cast) abort(403, 'キャストプロフィールがありません');

    // 🔍 現在進行中のマッチをチェック
    $activeMatch = \App\Models\CallMatch::where('cast_profile_id', $cast->id)
        ->where('status', 'started')
        ->latest('started_at')
        ->first();

    // 進行中のマッチがあれば、そのままアクティブ画面へ遷移
    if ($activeMatch) {
        return redirect()->route('matches.active', ['match' => $activeMatch->id]);
    }

    // 通常の「マッチ開始画面」へ
    $availableRequests = \App\Models\CallRequestCast::where('cast_profile_id', $cast->id)
        ->where('status', 'accepted')
        ->latest()
        ->with(['callRequest.user:id,name'])
        ->get();

    return inertia('Cast/MatchStart', [
        'cast' => $cast,
        'requests' => $availableRequests,
    ]);
}

    /** マッチ開始 */
    public function start(Request $request)
    {
        $data = $request->validate([
            'cast_profile_id' => ['required', 'exists:cast_profiles,id'],
            'call_request_id' => ['required', 'integer'],
            'call_request_cast_id' => ['nullable', 'integer'],
            'duration' => ['required', 'in:60,120,180'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $match = CallMatch::create([
            'call_request_id'      => $data['call_request_id'],
            'call_request_cast_id' => $data['call_request_cast_id'] ?? null,
            'cast_profile_id'      => $data['cast_profile_id'],
            'duration_minutes'     => $data['duration'],
            'started_at'           => now(),
            'latitude'             => $data['latitude'] ?? null,
            'longitude'            => $data['longitude'] ?? null,
            'status'               => 'started',
        ]);

        // ✅ 通知情報の準備
        try {
            $token = config('services.line.channel_access_token');
            $to = env('LINE_ADMIN_USER_ID');

            $cast = CastProfile::find($data['cast_profile_id']);
            $requestCast = \App\Models\CallRequestCast::with('callRequest.user')
                ->find($data['call_request_cast_id']);

            $userName = $requestCast?->callRequest?->user?->name ?? '不明なユーザー';
            $userId   = $requestCast?->callRequest?->user?->id ?? '-';

            // ✅ 通知メッセージ本文
            $msg = "🎬 【マッチ開始】\n"
                . "キャスト: {$cast->nickname}\n"
                . "依頼ユーザー: {$userName} (ID: {$userId})\n"
                . "時間: {$data['duration']}分\n"
                . "開始時刻: " . now()->format('H:i');

            if ($data['latitude']) {
                $msg .= "\n位置情報: https://www.google.com/maps?q={$data['latitude']},{$data['longitude']}";
            }

            Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', [
                'to' => $to,
                'messages' => [['type' => 'text', 'text' => $msg]],
            ]);
        } catch (\Throwable $e) {
            Log::error('LINE通知失敗: ' . $e->getMessage());
        }

        return Inertia::render('Cast/MatchActive', [
            'match' => $match,
        ]);
    }


    /** 延長処理 */
    public function extend(Request $request, CallMatch $match)
    {
        $hours = $request->validate([
            'hours' => ['required', 'in:1,2']
        ])['hours'];

        $addMinutes = $hours * 60;
        $match->increment('duration_minutes', $addMinutes);

        try {
            $token = config('services.line.channel_access_token');
            $to = env('LINE_ADMIN_USER_ID');
            $msg = "⏱ 【マッチ延長】\n"
                . "キャスト: {$match->castProfile->nickname}\n"
                . "＋{$hours}時間（計: {$match->duration_minutes}分）";
            Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', [
                'to' => $to,
                'messages' => [['type' => 'text', 'text' => $msg]],
            ]);
        } catch (\Throwable $e) {
            Log::error('LINE通知失敗(延長): ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    /** 終了処理 */
    public function end(Request $request, CallMatch $match)
    {
        $match->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        try {
            $token = config('services.line.channel_access_token');
            $to = env('LINE_ADMIN_USER_ID');
            $cast = $match->castProfile;
            $msg = "🏁 【マッチ終了】\n"
                . "キャスト: {$cast->nickname}\n"
                . "開始: {$match->started_at}\n"
                . "終了: " . now()->format('H:i');
            Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', [
                'to' => $to,
                'messages' => [['type' => 'text', 'text' => $msg]],
            ]);
        } catch (\Throwable $e) {
            Log::error('LINE通知失敗(終了): ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }
    public function active(CallMatch $match)
    {
        // キャスト本人以外はアクセス禁止
        $castId = auth()->user()->castProfile->id ?? null;
        if ($match->cast_profile_id !== $castId) {
            abort(403, 'このマッチにはアクセスできません。');
        }

        return Inertia::render('Cast/MatchActive', [
            'match' => $match,
        ]);
    }
    
}
