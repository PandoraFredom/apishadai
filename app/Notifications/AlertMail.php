<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertMail extends Notification
{
    /**
     * @param  array{
     *     from: string,
     *     cc: string|null,
     *     subject: string,
     *     data: array<string, mixed>
     * }  $details
     */
    public function __construct(private readonly array $details) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->from($this->details['from'], 'Shadai Alerts')
            ->subject($this->details['subject'])
            ->markdown('Notifications.RegistroESTemplate', [
                'subject' => $this->details['subject'],
                'body' => $this->details['data'],
            ]);

        if ($this->details['cc'] !== null) {
            $mailMessage->cc($this->details['cc']);
        }

        return $mailMessage;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'data' => $this->details['data'],
            'subject' => $this->details['subject'],
            'from' => $this->details['from'],
        ];
    }
}
