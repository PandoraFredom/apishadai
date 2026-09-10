<?php

namespace App\Interfaces\Farma;

use App\Models\Farma\ProdCategoria;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProdCategoriaService
{
    public function paginate(int $perPage = 30): LengthAwarePaginator;

    public function create(array $data): ProdCategoria;

    public function find(int $id): ?ProdCategoria;

    public function update(int $id, array $data): ?ProdCategoria;

    public function delete(int $id): bool;
}
