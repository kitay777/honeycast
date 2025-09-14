<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model {
    protected $fillable = ['is_group'];
    public function participants(){ return $this->hasMany(ConversationParticipant::class); }
    public function messages(){ return $this->hasMany(Message::class); }
}

