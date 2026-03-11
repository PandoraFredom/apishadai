<?php

namespace App\Jobs;

use App\Models\AppConfig;
use App\Models\WorkLunch;
use App\Notifications\AlertMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendWorkLunchAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $workLunchId)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $workLunch = WorkLunch::query()
            ->with(['User', 'Device.Stock'])
            ->find($this->workLunchId);

        if (!$workLunch) {
            return;
        }

        $mails = AppConfig::query()->first();

        if (!$mails || !$mails->mail_cc1) {
            return;
        }

        $details = [
            'from' => $mails->mail_alert,
            'cc' => $mails->mail_cc2,
            'subject' => 'Registro E/S',
            'data' => [
                'id' => $workLunch->id,
                'user' => $workLunch->User?->nombre,
                'start_time' => $workLunch->wkstart_time,
                'end_time' => $workLunch->wkend_time,
                'lunch_start_time' => $workLunch->lunch_start_time,
                'lunch_end_time' => $workLunch->lunch_end_time,
                'stock' => $workLunch->Device?->Stock?->descripcion,
                'lunchDuration' => $workLunch->lunch_duration,
                'workDuration' => $workLunch->work_duration,
            ],
        ];

        Notification::route('mail', $mails->mail_cc1)
            ->notify(new AlertMail($details));
    }
}
