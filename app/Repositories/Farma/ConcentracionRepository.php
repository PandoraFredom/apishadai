<?php

namespace App\Repositories\Farma;

use App\Interfaces\Farma\ConcentracionService;
use App\Models\Farma\Concentracion;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ConcentracionRepository implements ConcentracionService
{
    public function __construct(private readonly Concentracion $model) {}

    public function paginate(int $perPage = 30): LengthAwarePaginator
    {
        return $this->model
            ->newQuery()
            ->orderByDesc('id')
            ->paginate(max(1, min($perPage, 100)));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Concentracion
    {
        return DB::transaction(function () use ($data): Concentracion {
            $model = $this->model->newQuery()->create($data);

            return $model;
        });
    }

    public function find(int $id): ?Concentracion
    {
        return $this->model
            ->newQuery()
            ->find($id);
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): ?Concentracion
    {
        return DB::transaction(function () use ($id, $data): ?Concentracion {
            $model = $this->model->newQuery()->lockForUpdate()->find($id);

            if ($model === null) {
                return null;
            }

            $model->update($data);

            return $model->fresh();
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $model = $this->model->newQuery()->lockForUpdate()->find($id);

            return $model !== null && (bool) $model->delete();
        });
    }
}
