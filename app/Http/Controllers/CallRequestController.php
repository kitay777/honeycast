<?php

namespace App\Http\Controllers;

use App\Models\CallRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Coupon;
use LINE\LINEBot\Constant\HTTPHeader;

use Illuminate\Support\Facades\Log;
use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Configuration;


class CallRequestController extends Controller
{
public function create()
{
    // ✅ 有効クーポン取得
    $coupons = \App\Models\Coupon::query()
        ->where('active', true)
        ->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
        })
        ->orderByDesc('id')
        ->get(['id', 'name', 'discount_points', 'image_path'])
        ->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'points' => $c->discount_points,
            'image_url' => $c->image_path
                ? asset('storage/' . $c->image_path)
                : '/assets/imgs/placeholder.png',
        ]);

    // ✅ 呼ぶフォームを表示（Vue: resources/js/Pages/Call/Create.vue）
    return inertia('Call/Create', [
        'coupons' => $coupons,
    ]);
}

public function store(Request $request)
{
    $data = $request->validate([
        'place' => 'required|string|max:255',
        'cast_count' => 'required|integer|min:1',
        'guest_count' => 'required|integer|min:1',
        'nomination' => 'nullable|string|max:255',
        'date' => 'required|date',
        'start_time' => 'required|string',
        'end_time' => 'required|string',
        'set_plan' => 'nullable|string|max:50',
        'game_option' => 'nullable|string|max:100',
        'note' => 'nullable|string|max:500',
        'coupon_id' => 'nullable|exists:coupons,id',
    ]);

    $data['user_id'] = auth()->id();
    $call = \App\Models\CallRequest::create($data);

    // ✅ LINE通知
    try {
        $conf = new Configuration();
        $conf->setAccessToken(env('LINE_MESSAGE_ACCESS_TOKEN'));
        $client = new MessagingApiApi(null, $conf);

        $msg = "📢 呼び出し申請がありました\n\n"
            ."📍 場所: {$data['place']}\n"
            ."👩‍💼 キャスト人数: {$data['cast_count']}名\n"
            ."🧑‍🤝‍🧑 お客様人数: {$data['guest_count']}名\n"
            ."📅 日時: {$data['date']} {$data['start_time']}〜{$data['end_time']}\n"
            .(!empty($data['nomination']) ? "🎯 指名: {$data['nomination']}\n" : '')
            .(!empty($data['coupon_id']) 
                ? "🎁 使用クーポン: ".(\App\Models\Coupon::find($data['coupon_id'])->name ?? '（不明）')."\n" 
                : '')
            ."✏️ 備考: ".($data['note'] ?: '（なし）');

        // 管理者LINE ID（.envに追加）
        $to = env('LINE_ADMIN_USER_ID');
        if (!$to) {
            Log::warning('LINE_ADMIN_USER_ID が設定されていません');
        }

        $client->pushMessage([
            'to' => $to,
            'messages' => [
                new TextMessage(['type' => 'text', 'text' => $msg]),
            ],
        ]);

        Log::info('LINE通知送信', ['to' => $to, 'msg' => $msg]);
    } catch (\Throwable $e) {
        Log::error('LINE通知失敗', ['error' => $e->getMessage()]);
    }

    return redirect()->route('dashboard')->with('success', '呼び出しを送信しました！');
}

    public function show(CallRequest $callRequest)
    {
        $this->authorizeView($callRequest);

        return Inertia::render('Call/Show', [
            'call' => [
                'id'          => $callRequest->id,
                'place'       => $callRequest->place,
                'cast_count'  => $callRequest->cast_count,
                'guest_count' => $callRequest->guest_count,
                'nomination'  => $callRequest->nomination,
                'date'        => $callRequest->date->toDateString(),
                'start_time'  => substr($callRequest->start_time, 0, 5),
                'end_time'    => substr($callRequest->end_time, 0, 5),
                'set_plan'    => $callRequest->set_plan,
                'game_option' => $callRequest->game_option,
                'note'        => $callRequest->note,
                'status'      => $callRequest->status,
            ],
        ]);
    }

    private function authorizeView(CallRequest $callRequest): void
    {
        if ($callRequest->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
