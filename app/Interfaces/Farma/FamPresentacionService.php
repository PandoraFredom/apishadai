<?php

namespace App\Interfaces\Farma;

use App\Models\Farma\FamPresentacion;
use Illuminate\Pagination\LengthAwarePaginator;

interface FamPresentacionService
{
    public function paginate(int $perPage = 30): LengthAwarePaginator;

    public function create(array $data): FamPresentacion;

    public function find(int $id): ?FamPresentacion;

    public function update(int $id, array $data): ?FamPresentacion;

    public function delete(int $id): bool;
}
