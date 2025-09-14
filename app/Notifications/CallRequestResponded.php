<?php

namespace App\Notifications;

use App\Models\CallRequest;
use App\Models\CallRequestCast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CallRequestResponded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CallRequest $req, public CallRequestCast $assignment, public string $decision) {}

    public function via(object $notifiable): array
    {
        return ['mail','database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('キャストから回答がありました')
            ->line("リクエスト #{$this->req->id} / {$this->req->date} {$this->req->start_time}–{$this->req->end_time}")
            ->line("キャスト: ".($this->assignment->castProfile->nickname ?? $this->assignment->castProfile->user?->name ?? '#'.$this->assignment->cast_profile_id))
            ->line("回答: ".($this->decision === 'accept' ? '参加' : '辞退'))
            ->action('管理画面を開く', route('admin.requests.index',['selected_id'=>$this->req->id]));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'call_request_id' => $this->req->id,
            'assignment_id'   => $this->assignment->id,
            'decision'        => $this->decision,
        ];
    }
}
