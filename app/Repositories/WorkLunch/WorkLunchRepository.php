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

    public function todayForUser(int $userId, string $date): ?WorkLunch
    {
        return $this->model->newQuery()
            ->where('usuario', $userId)
            ->where(function ($query) use ($date) {
                $query->where('work_date', $date)
                    ->orWhere(function ($query) use ($date) {
                        $query->whereNull('work_date')
                            ->whereBetween('wkstart_time', [
                                $date.' 00:00:00',
                                $date.' 23:59:59',
                            ]);
                    });
            })
            ->first();
    }

    public function workService(int $userId, string $dateTime, int $deviceId): WorkLunch
    {
        $date = substr($dateTime, 0, 10);

        $result = DB::transaction(function () use ($userId, $date, $dateTime, $deviceId) {
            $session = $this->findByUserAndDateForUpdate($userId, $date);

            if ($session) {
                if ($session->wkstart_time && $session->wkend_time) {
                    throw new \DomainException('La jornada laboral del día ya fue registrada.', 400);
                }

                if ($session->lunch_start_time && ! $session->lunch_end_time) {
                    throw new \DomainException('Finaliza el almuerzo antes de registrar la salida.', 400);
                }

                $session->update(['wkend_time' => $dateTime]);

                return [
                    'session' => $session->refresh(),
                    'event' => 'work_ended',
                ];
            }

            return [
                'session' => $this->model->newQuery()->create([
                    'usuario' => $userId,
                    'device' => $deviceId,
                    'wkstart_time' => $dateTime,
                    'work_date' => $date,
                ]),
                'event' => 'work_started',
            ];
        }, 3);

        $this->dispatchAlert($result['session'], $result['event']);

        return $result['session'];
    }

    public function lunchService(int $userId, string $dateTime): WorkLunch
    {
        $date = substr($dateTime, 0, 10);

        $result = DB::transaction(function () use ($userId, $date, $dateTime) {
            $session = $this->findByUserAndDateForUpdate($userId, $date);

            if (! $session) {
                throw new \DomainException('Primero registra la entrada de la jornada laboral.', 400);
            }

            if ($session->wkend_time) {
                throw new \DomainException('La jornada laboral ya finalizó.', 400);
            }

            if ($session->lunch_start_time && $session->lunch_end_time) {
                throw new \DomainException('El almuerzo del día ya fue registrado.', 400);
            }

            $field = $session->lunch_start_time ? 'lunch_end_time' : 'lunch_start_time';
            $session->update([$field => $dateTime]);

            return [
                'session' => $session->refresh(),
                'event' => $field === 'lunch_start_time' ? 'lunch_started' : 'lunch_ended',
            ];
        }, 3);

        $this->dispatchAlert($result['session'], $result['event']);

        return $result['session'];
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

        $startCollection = $byStart instanceof Collection ? $byStart : new Collection;
        $endCollection = $byEnd   instanceof Collection ? $byEnd : new Collection;

        return $startCollection
            ->merge($endCollection)
            ->unique('id')
            ->sortByDesc('id')
            ->values();
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
                                $date.' 00:00:00',
                                $date.' 23:59:59',
                            ]);
                    });
            })
            ->lockForUpdate()
            ->first();
    }

    private function dispatchAlert(WorkLunch $session, string $event): void
    {
        SendWorkLunchAlertJob::dispatch(
            $session->id,
            $event,
            [
                'wkstart_time' => $session->wkstart_time,
                'wkend_time' => $session->wkend_time,
                'lunch_start_time' => $session->lunch_start_time,
                'lunch_end_time' => $session->lunch_end_time,
            ],
        );
    }
}
