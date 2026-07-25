<?php

namespace App\Repositories\WorkLunch;

use App\Interfaces\WorkLunch\WorkLunchService;
use App\Jobs\SendWorkLunchAlertJob;
use App\Models\WorkLunch;
use App\Repositories\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WorkLunchRepository extends Repository implements WorkLunchService
{
    public function __construct(WorkLunch $model)
    {
        parent::__construct($model);
        $this->defaultRelations = ['User', 'Device.Stock'];
        $this->orderBy = ['id', 'DESC'];
    }

    public function workService(int $userId, string $dateTime, int $deviceId): WorkLunch
    {
        $date    = substr($dateTime, 0, 10);

        $result = DB::transaction(function () use ($userId, $date, $dateTime, $deviceId) {
            $session = $this->findByUserAndDateForUpdate($userId, $date);

            if ($session) {
                if ($session->wkstart_time && $session->wkend_time) {
                    throw new \DomainException('Datos del dia ya registrados', 400);
                }

                $session->update(['wkend_time' => $dateTime]);

                return $session->refresh();
            }

            return $this->model->newQuery()->create([
                'usuario'      => $userId,
                'device'       => $deviceId,
                'wkstart_time' => $dateTime,
                'work_date'    => $date,
            ]);
        }, 3);

        $this->dispatchAlert($result->id);

        return $result;
    }

    public function lunchService(int $userId, string $dateTime): WorkLunch
    {
        $date    = substr($dateTime, 0, 10);

        $result = DB::transaction(function () use ($userId, $date, $dateTime) {
            $session = $this->findByUserAndDateForUpdate($userId, $date);

            if (!$session) {
                throw new \DomainException('No se encontro una sesion de trabajo para este dia', 400);
            }

            if ($session->lunch_start_time && $session->lunch_end_time) {
                throw new \DomainException('Datos del dia ya registrados', 400);
            }

            $field = $session->lunch_start_time ? 'lunch_end_time' : 'lunch_start_time';
            $session->update([$field => $dateTime]);

            return $session->refresh();
        }, 3);

        $this->dispatchAlert($result->id);

        return $result;
    }

    public function findByUserBetweenDates(int $userId, string $startDate, string $endDate): Collection
    {
        $byStart = $this->whereList([
            ['usuario', '=', $userId],
            ['wkstart_time', '>=', $startDate],
            ['wkstart_time', '<=', $endDate],
        ]);

        $byEnd = $this->whereList([
            ['usuario', '=', $userId],
            ['wkend_time', '>=', $startDate],
            ['wkend_time', '<=', $endDate],
        ]);

        $startCollection = $byStart instanceof Collection ? $byStart : new Collection();
        $endCollection   = $byEnd   instanceof Collection ? $byEnd   : new Collection();

        return $startCollection
            ->merge($endCollection)
            ->unique('id')
            ->sortByDesc('id')
            ->values();
    }

    private function findByUserAndDate(int $userId, string $date): ?WorkLunch
    {
        $result = $this->whereFirst([
            ['usuario', '=', $userId],
            ['wkstart_time', '>=', $date . ' 00:00:00'],
            ['wkstart_time', '<=', $date . ' 23:59:59'],
        ]);

        return $result instanceof WorkLunch ? $result : null;
    }

    private function findByUserAndDateForUpdate(int $userId, string $date): ?WorkLunch
    {
        return $this->model->newQuery()
            ->where('usuario', $userId)
            ->where(function ($query) use ($date) {
                $query->where('work_date', $date)
                    ->orWhere(function ($query) use ($date) {
                        $query->whereNull('work_date')
                            ->whereBetween('wkstart_time', [
                                $date . ' 00:00:00',
                                $date . ' 23:59:59',
                            ]);
                    });
            })
            ->lockForUpdate()
            ->first();
    }

    private function dispatchAlert(int $sessionId): void
    {
        SendWorkLunchAlertJob::dispatch($sessionId)->afterResponse();
    }
}
