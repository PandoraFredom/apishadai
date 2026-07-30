<?php

namespace Tests\Unit;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage as IlluminateSentMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class MailDeliveryLoggingTest extends TestCase
{
    public function test_it_logs_the_message_id_after_the_transport_accepts_an_email(): void
    {
        Log::spy();
        $email = (new Email)
            ->from('alerts@example.test')
            ->to('recipient@example.test')
            ->subject('Entrada registrada')
            ->text('Contenido de prueba');
        $sent = new IlluminateSentMessage(
            new SymfonySentMessage($email, Envelope::create($email)),
        );

        Event::dispatch(new MessageSent($sent));

        Log::shouldHaveReceived('info')
            ->once()
            ->with(
                'Correo aceptado por el transporte SMTP.',
                Mockery::on(fn (array $context): bool => filled($context['message_id'] ?? null)
                    && ($context['subject'] ?? null) === 'Entrada registrada'
                    && count($context['to_hashes'] ?? []) === 1),
            );
    }
}
