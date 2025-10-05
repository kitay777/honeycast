<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallRequest;
use App\Models\CallRequestCast;
use App\Models\CastProfile;
use App\Models\CastShift;
use App\Notifications\CallRequestInvited;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CallRequestController extends Controller
{
    /** 一覧＋詳細（候補リスト付き） */
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();   // pending/assigned/closed など
        $date   = $request->string('date')->toString();     // YYYY-MM or YYYY-MM-DD
        $selId  = $request->integer('selected_id') ?: null;

        $q = CallRequest::with(['user:id,name,email'])
            ->when($status !== '', fn ($x) => $x->where('status', $status))
            ->when($date !== '', function ($x) use ($date) {
                if (strlen($date) === 7) { // YYYY-MM
                    $x->where('date', '>=', "$date-01")->where('date', '<=', "$date-31");
                } else {
                    $x->where('date', $date);
                }
            })
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $selected   = null;
        $candidates = [];

        if ($selId) {
            $selected = CallRequest::with([
                'user:id,name,email',
                'assignments.castProfile:id,user_id,nickname',
                'assignments.castProfile.user:id,name,email',
            ])->find($selId);

            if ($selected) {
                // 同日・時間内で空きのあるシフトから候補キャストを抽出
                $candidates = CastShift::with(['castProfile:id,user_id,nickname', 'castProfile.user:id,name,email'])
                    ->where('date', $selected->date)
                    ->where('start_time', '<=', $selected->start_time)
                    ->where('end_time', '>=', $selected->end_time)
                    ->where('is_reserved', false)
                    ->orderBy('cast_profile_id')
                    ->get()
                    ->map(function ($s) {
                        $nick = $s->castProfile->nickname;
                        return [
                            'id'    => $s->cast_profile_id,
                            // ★ ユーザー名で補完しない（キャストのニックネームのみ）
                            'label' => ($nick && $nick !== '') ? $nick : '(無名)#' . $s->cast_profile_id,
                            'email' => optional($s->castProfile->user)->email,
                        ];
                    })
                    ->unique('id')
                    ->values();
            }
        }

        // 手動割当用のキャスト一覧（最大200件）
        $allCasts = CastProfile::with('user:id,name,email')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'user_id', 'nickname'])
            ->map(fn ($c) => [
                'id'      => $c->id,
                'user_id' => $c->user_id,
                'label'   => ($c->nickname && $c->nickname !== '') ? $c->nickname : '(無名)#' . $c->id,
                'nickname'=> $c->nickname,
                'email'   => optional($c->user)->email,
            ]);

        return Inertia::render('Admin/Requests/Index', [
            'requests'   => $q,
            'selected'   => $selected,
            'candidates' => $candidates,
            'casts'      => $allCasts,
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
                            'req_id' => $push['req_id'], 'to_user' => $push['user_id'],
                            'status' => $res->status(), 'body' => $res->body(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('LINE push exception', [
                        'req_id' => $push['req_id'], 'to_user' => $push['user_id'], 'ex' => $e->getMessage(),
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
