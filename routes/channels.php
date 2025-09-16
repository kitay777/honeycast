<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ChatThread;

Broadcast::channel('chat.thread.{threadId}', function ($user, $threadId) {
    $thread = ChatThread::find($threadId);
    return $thread && in_array($user->id, [$thread->user_one_id, $thread->user_two_id]);
});