<?php

namespace App\Repositories\Farma;

use App\Interfaces\Farma\ProductCatalogService;
use App\Services\Farma\ProductCatalogRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductCatalogRepository implements ProductCatalogService
{
    public function __construct(private readonly ProductCatalogRegistry $registry) {}

    public function paginate(string $catalog, int $perPage = 30): LengthAwarePaginator
    {
        return $this->registry->model($catalog)
            ->newQuery()
            ->with($this->registry->relations($catalog))
            ->orderByDesc('id')
            ->paginate(max(1, min($perPage, 100)));
    }

    /** @param array<string, mixed> $data */
    public function create(string $catalog, array $data): Model
    {
        return DB::transaction(function () use ($catalog, $data): Model {
            $model = $this->registry->model($catalog)->newQuery()->create($data);

            return $model->load($this->registry->relations($catalog));
        });
    }

    public function find(string $catalog, int $id): ?Model
    {
        return $this->registry->model($catalog)
            ->newQuery()
            ->with($this->registry->relations($catalog))
            ->find($id);
    }

    /** @param array<string, mixed> $data */
    public function update(string $catalog, int $id, array $data): ?Model
    {
        return DB::transaction(function () use ($catalog, $id, $data): ?Model {
            $model = $this->registry->model($catalog)->newQuery()->lockForUpdate()->find($id);

            if ($model === null) {
                return null;
            }

            $model->update($data);

            return $model->fresh($this->registry->relations($catalog));
        });
    }

    public function delete(string $catalog, int $id): bool
    {
        return DB::transaction(function () use ($catalog, $id): bool {
            $model = $this->registry->model($catalog)->newQuery()->lockForUpdate()->find($id);

            return $model !== null && (bool) $model->delete();
        });
    }
}
