<?php

namespace App\Repositories\Farma;

use App\Interfaces\Farma\ProductActiveService;
use App\Models\Farma\ProdActivo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductActiveRepository implements ProductActiveService
{
    /** @var array<int, string> */
    private const RELATIONS = [
        'productoDetalle:id,codigo,codigobar,descripcion',
        'principioActivo.concentracionDetalle',
    ];

    public function __construct(private readonly ProdActivo $model) {}

    public function paginate(int $perPage = 30): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(self::RELATIONS)
            ->orderByDesc('id')
            ->paginate(max(1, min($perPage, 100)));
    }

    public function find(int $id): ?ProdActivo
    {
        return $this->model->newQuery()->with(self::RELATIONS)->find($id);
    }

    /** @param array{producto: int, pactivo: int} $data */
    public function create(array $data): ProdActivo
    {
        return DB::transaction(function () use ($data): ProdActivo {
            /** @var ProdActivo $association */
            $association = $this->model->newQuery()->create($data);

            return $association->load(self::RELATIONS);
        });
    }

    /** @param array{producto: int, pactivo: int} $data */
    public function update(int $id, array $data): ?ProdActivo
    {
        return DB::transaction(function () use ($id, $data): ?ProdActivo {
            /** @var ProdActivo|null $association */
            $association = $this->model->newQuery()->lockForUpdate()->find($id);

            if ($association === null) {
                return null;
            }

            $association->update($data);

            return $association->fresh(self::RELATIONS);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $association = $this->model->newQuery()->lockForUpdate()->find($id);

            return $association !== null && (bool) $association->delete();
        });
    }
}
