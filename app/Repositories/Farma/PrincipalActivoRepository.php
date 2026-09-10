<?php

namespace App\Repositories\Farma;

use App\Interfaces\Farma\PrincipalActivoService;
use App\Models\Farma\PrincipalActivo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PrincipalActivoRepository implements PrincipalActivoService
{
    public function __construct(private readonly PrincipalActivo $model) {}

    public function paginate(int $perPage = 30): LengthAwarePaginator
    {
        return $this->model
            ->newQuery()
            ->with(['concentracionDetalle'])
            ->orderByDesc('id')
            ->paginate(max(1, min($perPage, 100)));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): PrincipalActivo
    {
        return DB::transaction(function () use ($data): PrincipalActivo {
            $model = $this->model->newQuery()->create($data);

            return $model->load(['concentracionDetalle']);
        });
    }

    public function find(int $id): ?PrincipalActivo
    {
        return $this->model
            ->newQuery()
            ->with(['concentracionDetalle'])
            ->find($id);
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): ?PrincipalActivo
    {
        return DB::transaction(function () use ($id, $data): ?PrincipalActivo {
            $model = $this->model->newQuery()->lockForUpdate()->find($id);

            if ($model === null) {
                return null;
            }

            $model->update($data);

            return $model->fresh(['concentracionDetalle']);
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
