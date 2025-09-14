<?php

// app/Http/Controllers/MessageController.php
namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MessageController extends Controller
{
    /** 会話リスト */
    public function index()
    {
        $userId = Auth::id();

        // 自分が参加している会話
        $convos = Conversation::query()
            ->whereHas('participants', fn($q)=>$q->where('user_id', $userId))
            ->with([
                // 相手（自分以外）
                'participants.user:id,name',
                // 最新メッセージ
                'messages' => fn($q)=>$q->latest()->limit(1),
            ])
            ->orderByDesc(
                Message::select('created_at')
                    ->whereColumn('conversation_id','conversations.id')
                    ->latest()
                    ->limit(1)
            )
            ->get();

        // 一覧表示用配列へ整形
$rows = $convos->map(function($c) use ($userId){
    $latest = $c->messages->first();
    $others = $c->participants->where('user_id','!=',$userId)->pluck('user');
    $other  = $others->first();

    $lastRead = optional($c->participants->firstWhere('user_id',$userId))->last_read_at;
    $unread = \App\Models\Message::where('conversation_id',$c->id)
        ->when($lastRead, fn($q)=>$q->where('created_at','>',$lastRead))
        ->where('user_id','!=',$userId)->count();

    return [
        'id'      => $c->id,
        'name'    => $other?->name ?? '（不明）',
        'avatar'  => null,
        'snippet' => $latest?->body ?? '（画像）',
        // ---- ここを修正 ----
        'at'      => $latest && $latest->created_at
                      ? $latest->created_at->locale('ja')->isoFormat('M/D HH:mm')
                      : null,
        // --------------------
        'unread'  => $unread,
    ];
});

        return Inertia::render('Messages/Index', ['conversations'=>$rows]);
    }

    /** 会話詳細（メッセージ表示） */
    public function show(Conversation $conversation)
    {
        $this->authorizeMember($conversation);

        $messages = $conversation->messages()
            ->with('user:id,name')
            ->orderBy('created_at')
            ->take(100) // 簡易
            ->get()
            ->map(fn($m)=>[
                'id'=>$m->id,
                'me'=> $m->user_id === Auth::id(),
                'name'=>$m->user->name,
                'body'=>$m->body,
                'image_path'=>$m->image_path,
                'at'=>optional($m->created_at)->locale('ja')->isoFormat('M/D HH:mm'),
            ]);

        // 既読更新
        ConversationParticipant::where('conversation_id',$conversation->id)
            ->where('user_id',Auth::id())
            ->update(['last_read_at'=>now()]);

        return Inertia::render('Messages/Show', [
            'conversation_id' => $conversation->id,
            'messages'        => $messages,
        ]);
    }

    /** 送信 */
    public function store(Request $request, Conversation $conversation)
    {
        $this->authorizeMember($conversation);

        $data = $request->validate([
            'body'  => ['nullable','string','max:4000'],
            'image' => ['nullable','image','max:4096'],
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('dm_images', 'public');
        }

        $msg = Message::create([
            'conversation_id'=>$conversation->id,
            'user_id'=>Auth::id(),
            'body'=>$data['body'] ?? null,
            'image_path'=>$path,
        ]);

        // 後で Pusher ブロードキャスト化
        return back();
    }

    /** 会話開始（相手 user_id 指定） */
    public function start(Request $request)
    {
        $data = $request->validate(['user_id'=>['required','exists:users,id']]);
        $me = Auth::id(); $other = (int)$data['user_id'];

        // 既存の 1:1 会話があればそれを返す
        $existing = Conversation::whereHas('participants', fn($q)=>$q->where('user_id',$me))
            ->whereHas('participants', fn($q)=>$q->where('user_id',$other))
            ->where('is_group',false)->first();

        if (!$existing) {
            $existing = Conversation::create(['is_group'=>false]);
            ConversationParticipant::create(['conversation_id'=>$existing->id,'user_id'=>$me]);
            ConversationParticipant::create(['conversation_id'=>$existing->id,'user_id'=>$other]);
        }

        return redirect()->route('messages.show', $existing->id);
    }

    private function authorizeMember(Conversation $conversation): void
    {
        if (!$conversation->participants()->where('user_id',Auth::id())->exists()) {
            abort(403);
        }
    }
}

