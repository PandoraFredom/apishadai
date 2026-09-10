<?php

namespace App\Interfaces\Farma;

use App\Models\Farma\Concentracion;
use Illuminate\Pagination\LengthAwarePaginator;

interface ConcentracionService
{
    public function paginate(int $perPage = 30): LengthAwarePaginator;

    public function create(array $data): Concentracion;

    public function find(int $id): ?Concentracion;

    public function update(int $id, array $data): ?Concentracion;

    public function delete(int $id): bool;
}
