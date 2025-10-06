<?php

namespace App\Http\Controllers;

use App\Models\CastProfile;
use App\Models\ChatThread;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ChatController extends Controller
{
    /**
     * キャストとのチャットを開始（既存あれば流用）→ /chat/{thread} へ
     */
    public function start(Request $request, CastProfile $cast)
    {
        abort_unless(Auth::check(), 403);

        $me      = $request->user()->id;
        $partner = $cast->user_id;

        if ($me === $partner) {
            return redirect()->route('chat.index');
        }

        $threadId = $this->ensureThread($me, $partner);

        return redirect()->route('chat.show', $threadId);
    }

    /**
     * 一覧
     */
    public function index(Request $request)
    {
        $me = $request->user()->id;

        $threads = ChatThread::query()
            ->where(fn($q) => $q->where('user_one_id', $me)->orWhere('user_two_id', $me))
            ->with([
                'lastMessage' => function ($q) {
                    // ★ テーブル修飾＋alias で曖昧さを解消
                    $q->selectRaw(
                        'chat_messages.id, ' .
                        'chat_messages.chat_thread_id AS chat_thread_id, ' .
                        'chat_messages.sender_id, ' .
                        'chat_messages.body, ' .
                        'chat_messages.created_at'
                    );
                },
                'userOne:id,name',
                'userTwo:id,name',
            ])
            ->withCount([
                'messages as unread_count' => function ($q) use ($me) {
                    $q->whereNull('read_at')->where('sender_id', '!=', $me);
                }
            ])
            ->orderByDesc(DB::raw('COALESCE(last_message_at, updated_at)'))
            ->paginate(20)
            ->through(function (ChatThread $t) use ($me) {
                $otherId = $t->user_one_id === $me ? $t->user_two_id : $t->user_one_id;
                $other   = $t->user_one_id === $me ? $t->userTwo : $t->userOne;

                return [
                    'id'           => $t->id,
                    'other'        => $other ? ['id'=>$otherId, 'name'=>$other->name] : null,
                    'last_message' => $t->lastMessage ? [
                        'body'       => $t->lastMessage->body,
                        'sender_id'  => $t->lastMessage->sender_id,
                        'created_at' => optional($t->lastMessage->created_at)->toIso8601String(),
                    ] : null,
                    'unread_count' => (int)$t->unread_count,
                    'updated_at'   => optional($t->last_message_at ?? $t->updated_at)->toIso8601String(),
                ];
            });

        return Inertia::render('Chat/Index', ['threads' => $threads]);
    }

    /**
     * スレッド表示（相手からの未読→既読）
     */
    public function show(Request $request, ChatThread $thread)
    {
        $me = $request->user()->id;
        abort_unless(in_array($me, [$thread->user_one_id, $thread->user_two_id]), 403);

        ChatMessage::where('chat_thread_id', $thread->id)
            ->whereNull('read_at')
            ->where('sender_id', '!=', $me)
            ->update(['read_at' => now()]);

        $thread->load(['messages.sender:id,name']);

        $otherId = $thread->user_one_id === $me ? $thread->user_two_id : $thread->user_one_id;

        return Inertia::render('Chat/Show', [
            'thread'   => [
                'id'            => $thread->id,
                'other_user_id' => $otherId,
            ],
            'messages' => $thread->messages->map(fn($m) => [
                'id'         => $m->id,
                'sender_id'  => $m->sender_id,
                'body'       => $m->body,
                'created_at' => $m->created_at->toIso8601String(),
                'read_at'    => $m->read_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * 送信
     */
    public function send(Request $request, ChatThread $thread)
    {
        $me = $request->user()->id;
        abort_unless(in_array($me, [$thread->user_one_id, $thread->user_two_id]), 403);

        $data = $request->validate(['body' => ['required','string','max:4000']]);

        $msg = ChatMessage::create([
            'chat_thread_id' => $thread->id,
            'sender_id'      => $me,
            'body'           => $data['body'],
        ]);

        // 配信（あれば）
        if (class_exists(\App\Events\MessageCreated::class)) {
            event(new \App\Events\MessageCreated($msg, $thread->id));
        }

        $thread->update(['last_message_at' => now()]);
        return back();
    }

    /* ================== helpers ================== */

    /**
     * 2者間のスレッドを取得 or 作成してIDを返す
     */
    private function ensureThread(int $a, int $b): int
    {
        // 小さい方を user_one に固定して重複を防止
        [$one, $two] = $a <= $b ? [$a, $b] : [$b, $a];

        $threadId = ChatThread::where(function ($q) use ($one, $two) {
                $q->where('user_one_id', $one)->where('user_two_id', $two);
            })
            ->value('id');

        if ($threadId) return (int)$threadId;

        return (int) ChatThread::insertGetId([
            'user_one_id'     => $one,
            'user_two_id'     => $two,
            'last_message_at' => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
}
