<?php

namespace App\Interfaces\Farma;

use App\Models\Farma\ProdActivo;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductActiveService
{
    public function paginate(int $perPage = 30): LengthAwarePaginator;
    public function find(int $id): ?ProdActivo;
    public function create(array $data): ProdActivo;
    public function update(int $id, array $data): ?ProdActivo;
    public function delete(int $id): bool;
}
