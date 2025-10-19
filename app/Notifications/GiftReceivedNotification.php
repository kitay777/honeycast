<?php
namespace App\Notifications;

use App\Models\{User, Gift};
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GiftReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(public User $sender, public Gift $gift, public ?string $message){}

    public function via($notifiable){ return ['database']; }

    public function toDatabase($notifiable){
        return [
            'type' => 'gift_received',
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'gift_id' => $this->gift->id,
            'gift_name' => $this->gift->name,
            'message' => $this->message,
        ];
    }
}
