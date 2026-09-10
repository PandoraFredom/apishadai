<?php

namespace App\Interfaces\Farma;

use App\Models\Farma\PrincipalActivo;
use Illuminate\Pagination\LengthAwarePaginator;

interface PrincipalActivoService
{
    public function paginate(int $perPage = 30): LengthAwarePaginator;

    public function create(array $data): PrincipalActivo;

    public function find(int $id): ?PrincipalActivo;

    public function update(int $id, array $data): ?PrincipalActivo;

    public function delete(int $id): bool;
}
