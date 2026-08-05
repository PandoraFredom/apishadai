<?php

namespace App\Http\Resources\Reportes;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkLunchRptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $workSeconds = $this->durationInSeconds(
            $this->wkstart_time,
            $this->wkend_time,
        );
        $lunchSeconds = $this->durationInSeconds(
            $this->lunch_start_time,
            $this->lunch_end_time,
        );
        $netWorkSeconds = $workSeconds === null
            ? null
            : max(0, $workSeconds - ($lunchSeconds ?? 0));

        return [
            'id' => $this->id,
            'work_date' => $this->formatDate($this->work_date),
            'usuario' => [
                'id' => $this->User?->id,
                'nombre' => $this->User?->nombre,
            ],
            'device' => [
                'id' => $this->Device?->id,
                'displayname' => $this->Device?->displayname,
                'stock' => [
                    'id' => $this->Device?->Stock?->id,
                    'descripcion' => $this->Device?->Stock?->descripcion,
                ],
            ],
            'wkstart_time' => $this->formatDateTime($this->wkstart_time),
            'wkend_time' => $this->formatDateTime($this->wkend_time),
            'lunch_start_time' => $this->formatDateTime($this->lunch_start_time),
            'lunch_end_time' => $this->formatDateTime($this->lunch_end_time),
            'work_minutes' => $this->toMinutes($workSeconds),
            'lunch_minutes' => $this->toMinutes($lunchSeconds),
            'net_work_minutes' => $this->toMinutes($netWorkSeconds),
            'work_duration' => $this->formatDuration($workSeconds),
            'lunch_duration' => $this->formatDuration($lunchSeconds),
            'net_work_duration' => $this->formatDuration($netWorkSeconds),
        ];
    }

    private function durationInSeconds(mixed $start, mixed $end): ?int
    {
        if (! $start || ! $end) {
            return null;
        }

        return max(
            0,
            (int) Carbon::parse($start)->diffInSeconds(Carbon::parse($end), false),
        );
    }

    private function toMinutes(?int $seconds): ?int
    {
        return $seconds === null ? null : intdiv($seconds, 60);
    }

    private function formatDuration(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60,
        );
    }

    private function formatDateTime(mixed $value): ?string
    {
        return $value
            ? Carbon::parse($value)->format('Y-m-d H:i:s')
            : null;
    }

    private function formatDate(mixed $value): ?string
    {
        return $value
            ? Carbon::parse($value)->format('Y-m-d')
            : null;
    }
}
