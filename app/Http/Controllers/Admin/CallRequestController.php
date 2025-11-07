<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallRequest;
use App\Models\CallRequestCast;
use App\Models\CastProfile;
use App\Models\CastShift;
use App\Models\Coupon;
use App\Notifications\CallRequestInvited;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CallRequestController extends Controller
{
    /** 一覧＋詳細（候補リスト付き） */
    // ...
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();
        $date   = $request->string('date')->toString();
        $selId  = $request->integer('selected_id') ?: null;

        // ✅ JOINして一覧にもクーポン名/コードを含める
        $q = CallRequest::query()
            ->with(['user:id,name,email'])
            ->leftJoin('coupons', 'call_requests.coupon_id', '=', 'coupons.id')
            ->select([
                'call_requests.*',
                'coupons.name as coupon_name',
                'coupons.code as coupon_code',
                'coupons.discount_points as coupon_discount',
            ])
            ->when($status !== '', fn($x) => $x->where('call_requests.status', $status))
            ->when($date !== '', function ($x) use ($date) {
                if (strlen($date) === 7) {
                    $x->where('call_requests.date', '>=', "$date-01")
                        ->where('call_requests.date', '<=', "$date-31");
                } else {
                    $x->where('call_requests.date', $date);
                }
            })
            ->orderByDesc('call_requests.id')
            ->paginate(30)
            ->withQueryString();

        $selected   = null;
        $candidates = [];
        $coupons    = collect();

        if ($selId) {
            $selected = CallRequest::with([
                'user:id,name,email',
                'assignments.castProfile:id,user_id,nickname',
                'assignments.castProfile.user:id,name,email',
            ])->find($selId);

            if ($selected) {
                // 候補キャスト
                $candidates = CastShift::with(['castProfile:id,user_id,nickname', 'castProfile.user:id,name,email'])
                    ->where('date', $selected->date)
                    ->where('start_time', '<=', $selected->start_time)
                    ->where('end_time', '>=', $selected->end_time)
                    ->where('is_reserved', false)
                    ->orderBy('cast_profile_id')
                    ->get()
                    ->map(fn($s) => [
                        'id'    => $s->cast_profile_id,
                        'label' => $s->castProfile->nickname ?: '(無名)#' . $s->cast_profile_id,
                        'email' => optional($s->castProfile->user)->email,
                    ])
                    ->unique('id')
                    ->values();

                // ✅ このリクエストに紐づくクーポン詳細
                if ($selected->coupon_id) {
                    $coupon = \App\Models\Coupon::query()
                        ->where('id', $selected->coupon_id)
                        ->first(['id', 'name', 'code', 'discount_points as discount', 'expires_at']);

                    if ($coupon) {
                        $coupons = collect([$coupon]);
                    }
                }
            }
        }

        // 全キャストリスト
        $allCasts = CastProfile::with('user:id,name,email')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'user_id', 'nickname'])
            ->map(fn($c) => [
                'id'      => $c->id,
                'user_id' => $c->user_id,
                'label'   => $c->nickname ?: '(無名)#' . $c->id,
                'nickname' => $c->nickname,
                'email'   => optional($c->user)->email,
            ]);

        return Inertia::render('Admin/Requests/Index', [
            'requests'   => $q,
            'selected'   => $selected,
            'candidates' => $candidates,
            'casts'      => $allCasts,
            'coupons'    => $coupons,
            'filters'    => ['status' => $status, 'date' => $date, 'selected_id' => $selId],
        ]);
    }


    /** 割当（招待）＋ LINE 通知（Quick Reply: 参加/辞退） */
    public function assign(Request $request, CallRequest $req)
    {
        $data = $request->validate([
            'cast_profile_id' => ['required', 'exists:cast_profiles,id'],
            'note'            => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($req, $data, $request) {
            // 既に同一キャストが割当済みなら再招待扱いで更新
            $assign = CallRequestCast::firstOrCreate(
                [
                    'call_request_id' => $req->id,
                    'cast_profile_id' => $data['cast_profile_id'],
                ],
                [
                    'assigned_by' => optional($request->user())->id,
                    'status'      => 'invited',
                    'note'        => $data['note'] ?? null,
                    'invited_at'  => now(),
                ]
            );

            if (!$assign->wasRecentlyCreated) {
                $assign->fill([
                    'status'     => 'invited',
                    'note'       => $data['note'] ?? $assign->note,
                    'invited_at' => now(),
                ])->save();
            }

            // 通知（メール/DB）
            $user = $assign->castProfile?->user;
            if ($user) {
                $user->notify(new CallRequestInvited($req, $assign));
            }

            // 依頼側の状態（運用に合わせて）
            if ($req->status === 'pending') {
                $req->status = 'assigned';
                $req->save();
            }

            // LINE push 用に必要情報だけを切り出し
            $push = null;
            if ($user?->line_user_id) {
                $push = [
                    'assign_id' => $assign->id,
                    'req_id'    => $req->id,
                    'date'      => (string) $req->date,
                    'st'        => substr((string) $req->start_time, 0, 5),
                    'et'        => substr((string) $req->end_time, 0, 5),
                    'place'     => $req->place ?: '（未指定）',
                    'note'      => (string) ($assign->note ?? ''),
                    'line'      => $user->line_user_id,
                    'user_id'   => $user->id,
                ];
            }

            DB::afterCommit(function () use ($push) {
                Log::info('LINE push try', $push ?: ['push' => null]);
                if (!$push) {
                    Log::info('LINE push skipped: user not linked');
                    return;
                }

                $token = config('services.line.channel_access_token');
                if (!$token) {
                    Log::warning('LINE push skipped: token missing', ['req_id' => $push['req_id']]);
                    return;
                }

                $text = "【新着招待】\n"
                    . "リクエスト #{$push['req_id']}\n"
                    . "{$push['date']} {$push['st']}–{$push['et']}\n"
                    . "場所: {$push['place']}\n"
                    . "メッセージ: " . ($push['note'] !== '' ? $push['note'] : '（なし）')
                    . "\n\n下のボタンで参加可否をご返信ください。";

                // ★ Quick Reply（postback: assign_id & action）
                $payload = [
                    'to'       => $push['line'],
                    'messages' => [[
                        'type' => 'text',
                        'text' => $text,
                        'quickReply' => [
                            'items' => [
                                [
                                    'type'   => 'action',
                                    'action' => [
                                        'type'  => 'postback',
                                        'label' => '参加する',
                                        'data'  => "assign_id={$push['assign_id']}&action=accept",
                                    ],
                                ],
                                [
                                    'type'   => 'action',
                                    'action' => [
                                        'type'  => 'postback',
                                        'label' => '辞退する',
                                        'data'  => "assign_id={$push['assign_id']}&action=decline",
                                    ],
                                ],
                            ],
                        ],
                    ]],
                ];

                try {
                    $res = Http::withToken($token)->asJson()
                        ->post('https://api.line.me/v2/bot/message/push', $payload);

                    if ($res->successful()) {
                        Log::info('LINE push ok', ['req_id' => $push['req_id'], 'to_user' => $push['user_id']]);
                    } else {
                        Log::warning('LINE push failed', [
                            'req_id' => $push['req_id'],
                            'to_user' => $push['user_id'],
                            'status' => $res->status(),
                            'body' => $res->body(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('LINE push exception', [
                        'req_id' => $push['req_id'],
                        'to_user' => $push['user_id'],
                        'ex' => $e->getMessage(),
                    ]);
                }
            });
        });

        return back()->with('success', '招待を送りました');
    }

    /** 割当解除 */
    public function unassign(Request $request, CallRequest $req, CallRequestCast $assignment)
    {
        abort_unless($assignment->call_request_id === $req->id, 404);
        $assignment->delete();
        return back()->with('success', '割当を解除しました');
    }

    /** リクエストの状態更新 */
    public function updateStatus(Request $request, CallRequest $req)
    {
        $request->validate(['status' => ['required', 'string', 'max:20']]);
        $req->status = $request->string('status');
        $req->save();
        return back()->with('success', 'ステータスを更新しました');
    }
}
