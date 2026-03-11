<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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
}
