<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    public function getAll(): Collection;

    public function paginate(): LengthAwarePaginator;

    public function findById(int $id): ?Model;

    public function findOrFail(int $id): Model;

    public function whereList(array $conditions): Collection;

    public function whereFirst(array $conditions): ?Model;

    public function create(array $data): bool;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function exists(array $conditions): bool;
}
