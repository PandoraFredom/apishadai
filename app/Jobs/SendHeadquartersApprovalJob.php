<?php

namespace App\Jobs;

use App\Mail\HeadquartersApprovalMail;
use App\Models\AppConfig;
use App\Services\Headquarters\HeadquartersClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendHeadquartersApprovalJob implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 90];

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly string $recipient,
        public readonly HeadquartersApprovalMail $mail,
        public readonly int $expiresAt,
    ) {}

    public function handle(HeadquartersClient $client): void
    {
        if (now()->timestamp >= $this->expiresAt) {
            Log::warning('El código de Headquarters venció antes de enviar el correo.', [
                'registration_id' => $this->mail->registrationId,
            ]);
            $this->revoke($client);

            return;
        }

        try {
            // Also update the destination of jobs queued before mail_cc1 changed.
            $recipient = trim((string) AppConfig::query()->value('mail_cc1'));
            if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Configura un correo válido en appconfig.mail_cc1.');
            }
            Mail::mailer('smtp')->to($recipient)->send($this->mail);
            Log::info('Autorización de Headquarters enviada por el worker.', [
                'registration_id' => $this->mail->registrationId, 'job_id' => $this->job?->getJobId(),
                'attempt' => $this->attempts(),
            ]);
        } catch (Throwable $exception) {
            $reason = match (true) {
                $exception->getMessage() === 'Configura un correo válido en appconfig.mail_cc1.' => $exception->getMessage(),
                str_contains($exception->getMessage(), '535') => 'SMTP rechazó la autenticación (535). Revisa la credencial del remitente.',
                default => 'No se pudo enviar el correo de Headquarters por SMTP.',
            };
            Log::warning($reason, ['registration_id' => $this->mail->registrationId,
                'exception' => $exception::class]);

            // Do not persist raw SMTP responses, credentials or the code in failed_jobs.
            throw new RuntimeException($reason);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('El correo de Headquarters agotó sus reintentos. Solicita un código nuevo.', [
            'registration_id' => $this->mail->registrationId,
            'exception' => $exception ? $exception::class : null,
        ]);
        $this->revoke(app(HeadquartersClient::class));
    }

    private function revoke(HeadquartersClient $client): void
    {
        try {
            $client->send('DELETE', 'registrations/'.$this->mail->registrationId);
        } catch (Throwable $exception) {
            Log::warning('No se pudo revocar la solicitud; vencerá automáticamente.', [
                'registration_id' => $this->mail->registrationId, 'exception' => $exception::class,
            ]);
        }
    }
}
