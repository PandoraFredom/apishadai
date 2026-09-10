<?php

namespace App\Interfaces\Farma;

use App\Models\Farma\FamAdministracion;
use Illuminate\Pagination\LengthAwarePaginator;

interface FamAdministracionService
{
    public function paginate(int $perPage = 30): LengthAwarePaginator;

    public function create(array $data): FamAdministracion;

    public function find(int $id): ?FamAdministracion;

    public function update(int $id, array $data): ?FamAdministracion;

    public function delete(int $id): bool;
}
