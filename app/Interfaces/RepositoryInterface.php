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


    public function create(array $data): bool;


    public function update(int $id, array $data): bool;


    public function delete(int $id): bool;
}
