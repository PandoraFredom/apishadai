<?php

namespace App\Interfaces\Farma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductCatalogService
{
    public function paginate(string $catalog, int $perPage = 30): LengthAwarePaginator;
    public function create(string $catalog, array $data): Model;
    public function find(string $catalog, int $id): ?Model;
    public function update(string $catalog, int $id, array $data): ?Model;
    public function delete(string $catalog, int $id): bool;
}
