<?php

namespace App\Repositories\Farma;

use App\Interfaces\Farma\FamiliaService;
use App\Models\Farma\Familia;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FamiliaRepository implements FamiliaService
{
    public function __construct(private readonly Familia $model) {}

    public function paginate(int $perPage = 30): LengthAwarePaginator
    {
        return $this->model
            ->newQuery()
            ->with(['presentacionDetalle', 'administracionDetalle'])
            ->orderByDesc('id')
            ->paginate(max(1, min($perPage, 100)));
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Familia
    {
        return DB::transaction(function () use ($data): Familia {
            $model = $this->model->newQuery()->create($data);

            return $model->load(['presentacionDetalle', 'administracionDetalle']);
        });
    }

    public function find(int $id): ?Familia
    {
        return $this->model
            ->newQuery()
            ->with(['presentacionDetalle', 'administracionDetalle'])
            ->find($id);
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): ?Familia
    {
        return DB::transaction(function () use ($id, $data): ?Familia {
            $model = $this->model->newQuery()->lockForUpdate()->find($id);

            if ($model === null) {
                return null;
            }

            $model->update($data);

            return $model->fresh(['presentacionDetalle', 'administracionDetalle']);
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
