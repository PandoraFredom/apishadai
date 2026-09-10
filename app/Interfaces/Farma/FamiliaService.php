<?php

namespace App\Interfaces\Farma;

use App\Models\Farma\Familia;
use Illuminate\Pagination\LengthAwarePaginator;

interface FamiliaService
{
    public function paginate(int $perPage = 30): LengthAwarePaginator;

    public function create(array $data): Familia;

    public function find(int $id): ?Familia;

    public function update(int $id, array $data): ?Familia;

    public function delete(int $id): bool;
}
