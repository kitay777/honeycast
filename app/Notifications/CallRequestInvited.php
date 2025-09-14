<?php

namespace App\Notifications;

use App\Models\CallRequest;
use App\Models\CallRequestCast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class CallRequestInvited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CallRequest $req, public CallRequestCast $assignment) {}

    public function via(object $notifiable): array
    {
        return ['mail','database']; // Broadcasting使うなら 'broadcast' もOK
    }

    public function toMail(object $notifiable): MailMessage
    {
        $accept = URL::signedRoute('cast.invitations.respond', [
            'assignment' => $this->assignment->id,
            'decision'   => 'accept',
        ], now()->addHours(6));

        $decline = URL::signedRoute('cast.invitations.respond', [
            'assignment' => $this->assignment->id,
            'decision'   => 'decline',
        ], now()->addHours(6));

        return (new MailMessage)
            ->subject('出演依頼のご案内')
            ->greeting($notifiable->name.' 様')
            ->line("日時: {$this->req->date} {$this->req->start_time}–{$this->req->end_time}")
            ->line("場所: ".($this->req->place ?? '-'))
            ->action('参加する（承諾）', $accept)
            ->line('または以下から辞退できます。')
            ->action('辞退する', $decline);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'call_request_id' => $this->req->id,
            'assignment_id'   => $this->assignment->id,
            'date'            => $this->req->date,
            'start_time'      => $this->req->start_time,
            'end_time'        => $this->req->end_time,
        ];
    }
}
