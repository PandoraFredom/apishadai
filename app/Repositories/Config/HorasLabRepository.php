<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\HorasLabService;
use App\Models\HorasLab;
use App\Models\User;
use App\Repositories\Repository;
use Illuminate\Support\Facades\DB;

class HorasLabRepository extends Repository implements HorasLabService
{
    public function __construct(HorasLab $model)
    {
        parent::__construct($model);
        $this->defaultRelations = ['User'];
    }

    public function findByUser(int $userId): ?HorasLab
    {
        return $this->model->newQuery()
            ->with('User')
            ->where('usuario', $userId)
            ->first();
    }

    public function createSchedule(array $data): HorasLab
    {
        return DB::transaction(function () use ($data): HorasLab {
            $userId = (int) $data['usuario'];

            User::query()
                ->whereKey($userId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->model->newQuery()->where('usuario', $userId)->exists()) {
                throw new \DomainException(
                    'El usuario ya tiene una configuración de horas.',
                    409,
                );
            }

            return $this->model->newQuery()
                ->create($data)
                ->load('User');
        }, 3);
    }

    public function updateSchedule(int $id, array $data): ?HorasLab
    {
        return DB::transaction(function () use ($id, $data): ?HorasLab {
            $schedule = $this->model->newQuery()
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if (! $schedule) {
                return null;
            }

            $schedule->update($data);

            return $schedule->refresh()->load('User');
        }, 3);
    }
}
