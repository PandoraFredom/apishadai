<?php

namespace App\Interfaces\Farma;

use App\Models\Farma\ProdEstado;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProdEstadoService
{
    public function paginate(int $perPage = 30): LengthAwarePaginator;

    public function create(array $data): ProdEstado;

    public function find(int $id): ?ProdEstado;

    public function update(int $id, array $data): ?ProdEstado;

    public function delete(int $id): bool;
}
