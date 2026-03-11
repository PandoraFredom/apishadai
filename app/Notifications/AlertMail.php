<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertMail extends Notification implements ShouldQueue
{
    use Queueable;

    protected $details;

    public function __construct($details)
    {
        $this->details = $details;
    }


    public function via($notifiable): array
    {
        return ['mail'];
    }


    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->from($this->details['from'], 'ShadaiAlerts')
            ->subject($this->details['subject'])
            ->markdown('Notifications.RegistroESTemplate', [
                'subject' => $this->details['subject'],
                'body' =>  $this->details['data'],
                'url' => url('/')
            ])

            ->cc($this->details['cc']);
        return $mailMessage;
    }


    public function toArray($notifiable): array
    {
        return [
            'data' => $this->details['data'],
            'subject' => $this->details['subject'],
            'from' => $this->details['from'],
        ];
    }
}
