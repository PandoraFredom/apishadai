<?php

namespace App\Models;

use App\Notifications\AlertMail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class WorkLunch extends Model
{
    protected $table = 'worklunch';
    protected $fillable = [
        'usuario',
        'device',
        'wkstart_time',
        'wkend_time',
        'lunch_start_time',
        'lunch_end_time',
    ];

    public function User()
    {
        return $this->belongsTo(User::class, 'usuario');
    }

    public function Device()
    {
        return $this->belongsTo(Device::class, 'device');
    }
    public function getWorkDurationAttribute()
    {
        if ($this->wkend_time) {
            $workStart = Carbon::parse($this->wkstart_time);
            $workEnd = Carbon::parse($this->wkend_time);

            $lunchDuration = $workStart->diffInSeconds($workEnd);

            $hours = floor($lunchDuration / 3600);
            $minutes = floor(($lunchDuration % 3600) / 60);
            $seconds = $lunchDuration % 60;

            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return null;
    }
    public function getLunchDurationAttribute()
    {
        if ($this->lunch_start_time && $this->lunch_end_time) {
            $lunchStart = Carbon::parse($this->lunch_start_time);
            $lunchEnd = Carbon::parse($this->lunch_end_time);

            $lunchDuration = $lunchStart->diffInSeconds($lunchEnd);

            $hours = floor($lunchDuration / 3600);
            $minutes = floor(($lunchDuration % 3600) / 60);
            $seconds = $lunchDuration % 60;

            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return null;
    }
    public function sendAlert()
    {
        $currentInfo = $this->Device;
        $mails = AppConfig::all()->first();
        $details = [
            'from' => $mails->mail_alert,
            'cc' => $mails->mail_cc2,
            'subject' => 'Registro E/S',
            'data' => [
                'id' => $this->id,
                'user' => $this->user->nombre,
                'start_time' => $this->wkstart_time,
                'end_time' => $this->wkend_time,
                'lunch_start_time' => $this->lunch_start_time,
                'lunch_end_time' => $this->lunch_end_time,
                'stock' => $currentInfo->Stock->descripcion,
                'lunchDuration' => $this->getLunchDurationAttribute(),
                'workDuration' => $this->getWorkDurationAttribute(),
            ]
        ];
        Notification::route(
            'mail',
            $mails->mail_cc1
        )->notify(new AlertMail($details));
    }
}
