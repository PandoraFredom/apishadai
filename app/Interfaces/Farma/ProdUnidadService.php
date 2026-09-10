<?php

namespace App\Interfaces\Farma;

use App\Models\Farma\ProdUnidad;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProdUnidadService
{
    public function paginate(int $perPage = 30): LengthAwarePaginator;

    public function create(array $data): ProdUnidad;

    public function find(int $id): ?ProdUnidad;

    public function update(int $id, array $data): ?ProdUnidad;

    public function delete(int $id): bool;
}
