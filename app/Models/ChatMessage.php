<?php

// app/Models/ChatMessage.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['chat_thread_id','sender_id','body','read_at'];

    protected $casts = [
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function thread() { return $this->belongsTo(ChatThread::class); }
    public function sender() { return $this->belongsTo(\App\Models\User::class,'sender_id'); }
}

