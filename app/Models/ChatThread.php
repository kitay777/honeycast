<?php
// app/Models/ChatThread.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class ChatThread extends Model
{
    protected $fillable = ['user_one_id','user_two_id','last_message_at'];

    public function messages(): HasMany {
        return $this->hasMany(ChatMessage::class)->orderBy('id','asc');
    }

    // 最後のメッセージ（latestOfMany）
    public function lastMessage(): HasOne {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function userOne() { return $this->belongsTo(User::class, 'user_one_id'); }
    public function userTwo() { return $this->belongsTo(User::class, 'user_two_id'); }

    public function otherUserId(int $me): int {
        return $this->user_one_id === $me ? $this->user_two_id : $this->user_one_id;
    }
}
