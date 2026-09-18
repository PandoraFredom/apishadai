<?php

namespace App\Interfaces\Farma;

use App\Interfaces\RepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductService extends RepositoryInterface
{
    public function paginate(?int $perPage = null, ?string $search = null, ?int $laboratory = null, ?int $provider = null): LengthAwarePaginator;

    public function create(array $data, array $principiosActivos = []): bool;

    public function update(int $id, array $data, ?array $principiosActivos = null): bool;

    public function getImage(int $id): ?string;

    public function options(): array;
}
