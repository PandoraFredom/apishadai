<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class HeadquartersApprovalMail extends Mailable
{
    public function __construct(
        public readonly string $registrationId,
        public readonly string $deviceName,
        public readonly string $ip,
        public readonly ?string $stockName,
        public readonly string $code,
        public readonly int $minutes,
        public readonly ?int $sourceDeviceId = null,
        public readonly ?string $sourceDeviceName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Autorizar registro de Headquarters');
    }

    public function content(): Content
    {
        return new Content(text: 'emails.headquarters-approval');
    }
}
