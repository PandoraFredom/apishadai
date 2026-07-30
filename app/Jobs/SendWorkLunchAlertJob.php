<?php

namespace App\Jobs;

use App\Models\AppConfig;
use App\Models\WorkLunch;
use App\Notifications\AlertMail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SendWorkLunchAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    /**
     * @param  array{
     *     wkstart_time: string|null,
     *     wkend_time: string|null,
     *     lunch_start_time: string|null,
     *     lunch_end_time: string|null
     * }  $snapshot
     */
    public function __construct(
        private int $workLunchId,
        public readonly string $event,
        private array $snapshot,
    ) {}

    public function handle(): void
    {
        $workLunch = WorkLunch::query()
            ->with(['User', 'Device.Stock'])
            ->find($this->workLunchId);

        if (! $workLunch) {
            return;
        }

        $mails = AppConfig::query()->first();

        if (! $mails) {
            return;
        }

        $recipient = $this->validEmail($mails->mail_cc1);
        $sender = $this->validEmail($mails->mail_alert);
        $cc = $this->validEmail($mails->mail_cc2);

        if ($recipient === null || $sender === null) {
            return;
        }

        $eventLabel = $this->eventLabel();
        $details = [
            'from' => $sender,
            'cc' => $cc !== $recipient ? $cc : null,
            'subject' => $eventLabel.' · '.($workLunch->User?->nombre ?: 'Usuario'),
            'data' => [
                'id' => $workLunch->id,
                'user' => $workLunch->User?->nombre,
                'event' => $this->event,
                'event_label' => $eventLabel,
                'event_time' => $this->eventTime(),
                'start_time' => $this->snapshot['wkstart_time'],
                'end_time' => $this->snapshot['wkend_time'],
                'lunch_start_time' => $this->snapshot['lunch_start_time'],
                'lunch_end_time' => $this->snapshot['lunch_end_time'],
                'stock' => $workLunch->Device?->Stock?->descripcion,
                'lunchDuration' => $this->duration(
                    $this->snapshot['lunch_start_time'],
                    $this->snapshot['lunch_end_time'],
                ),
                'workDuration' => $this->duration(
                    $this->snapshot['wkstart_time'],
                    $this->snapshot['wkend_time'],
                ),
            ],
        ];

        Notification::route('mail', $recipient)
            ->notify(new AlertMail($details));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('El correo WorkLunch agotó sus reintentos.', [
            'work_lunch_id' => $this->workLunchId,
            'event' => $this->event,
            'exception' => $exception ? $exception::class : null,
            'error' => $exception?->getMessage(),
        ]);
    }

    private function eventLabel(): string
    {
        return match ($this->event) {
            'work_started' => 'Entrada registrada',
            'work_ended' => 'Salida registrada',
            'lunch_started' => 'Inicio de almuerzo registrado',
            'lunch_ended' => 'Fin de almuerzo registrado',
            default => 'Horario laboral actualizado',
        };
    }

    private function eventTime(): ?string
    {
        return match ($this->event) {
            'work_started' => $this->snapshot['wkstart_time'],
            'work_ended' => $this->snapshot['wkend_time'],
            'lunch_started' => $this->snapshot['lunch_start_time'],
            'lunch_ended' => $this->snapshot['lunch_end_time'],
            default => null,
        };
    }

    private function validEmail(mixed $email): ?string
    {
        $email = trim((string) $email);

        return Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email']],
        )->passes() ? $email : null;
    }

    private function duration(?string $start, ?string $end): ?string
    {
        if ($start === null || $end === null) {
            return null;
        }

        $seconds = (int) abs(Carbon::parse($start)->diffInSeconds(Carbon::parse($end)));

        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60,
        );
    }
}
