<?php

// app/Http/Controllers/ChatController.php
namespace App\Http\Controllers;

use App\Models\ChatThread;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    // 一覧
public function index(Request $request)
{
    $me = $request->user()->id;

    $threads = \App\Models\ChatThread::query()
        ->where(fn($q) => $q->where('user_one_id', $me)->orWhere('user_two_id', $me))

        // ▼ ここを修正：lastMessage はクロージャで fully qualified columns を選ぶ
        ->with([
            'lastMessage' => function ($q) {
                $q->select([
                    'chat_messages.id',
                    'chat_messages.chat_thread_id',
                    'chat_messages.sender_id',
                    'chat_messages.body',
                    'chat_messages.created_at',
                ]);
            },
            'userOne:id,name',
            'userTwo:id,name',
        ])

        // 未読数
        ->withCount([
            'messages as unread_count' => function ($q) use ($me) {
                $q->whereNull('read_at')->where('sender_id', '!=', $me);
            }
        ])

        // 並び順（last_message_at が NULL の場合は updated_at を代替）
        ->orderByDesc(DB::raw('COALESCE(last_message_at, updated_at)'))

        ->paginate(20)
        ->through(function ($t) use ($me) {
            $other = $t->user_one_id === $me ? $t->userTwo : $t->userOne;
            return [
                'id'           => $t->id,
                'other'        => $other ? ['id' => $other->id, 'name' => $other->name] : null,
                'last_message' => $t->lastMessage ? [
                    'body'       => $t->lastMessage->body,
                    'sender_id'  => $t->lastMessage->sender_id,
                    'created_at' => optional($t->lastMessage->created_at)->toIso8601String(),
                ] : null,
                'unread_count' => $t->unread_count,
                'updated_at'   => optional($t->last_message_at ?? $t->updated_at)->toIso8601String(),
            ];
        });

    return \Inertia\Inertia::render('Chat/Index', ['threads' => $threads]);
}

    // スレッド表示（未読 → 既読にする）
    public function show(Request $request, ChatThread $thread)
    {
        $me = $request->user()->id;
        abort_unless(in_array($me, [$thread->user_one_id, $thread->user_two_id]), 403);

        // 相手から来た未読を既読化
        ChatMessage::where('chat_thread_id',$thread->id)
            ->whereNull('read_at')
            ->where('sender_id','!=',$me)
            ->update(['read_at'=>now()]);

        $thread->load(['messages.sender:id,name']);

        return Inertia::render('Chat/Show', [
            'thread'   => [
                'id' => $thread->id,
                'other_user_id' => $thread->otherUserId($me),
            ],
            'messages' => $thread->messages->map(fn($m)=>[
                'id'=>$m->id,
                'sender_id'=>$m->sender_id,
                'body'=>$m->body,
                'created_at'=>$m->created_at->toIso8601String(),
                'read_at'=>$m->read_at?->toIso8601String(),
            ]),
        ]);
    }

    // 送信（既に作成済みのもの）
    public function send(Request $request, ChatThread $thread)
    {
        $me = $request->user()->id;
        abort_unless(in_array($me, [$thread->user_one_id, $thread->user_two_id]), 403);

        $data = $request->validate(['body'=>'required|string|max:4000']);

        $msg = \App\Models\ChatMessage::create([
            'chat_thread_id'=>$thread->id,
            'sender_id'=>$me,
            'body'=>$data['body'],
        ]);

        // ここでイベント発火（MessageCreated）も既にOKのはず
        event(new \App\Events\MessageCreated($msg, $thread->id));

        $thread->update(['last_message_at'=>now()]);
        return back();
    }
}
