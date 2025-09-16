<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChatMessage $message;
    public int $threadId;

    public function __construct(ChatMessage $message, int $threadId)
    {
        $this->message  = $message;
        $this->threadId = $threadId;
    }

    public function broadcastOn(): array
    {
        // 例: private-chat.thread.{id}
        return [new PrivateChannel("chat.thread.{$this->threadId}")];
    }

    public function broadcastAs(): string
    {
        return 'message.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->message->id,
            'chat_thread_id' => $this->message->chat_thread_id,
            'sender_id'      => $this->message->sender_id,
            'body'           => $this->message->body,
            'created_at'     => $this->message->created_at->toIso8601String(),
        ];
    }
}
