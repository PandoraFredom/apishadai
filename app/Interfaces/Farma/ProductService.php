<?php

namespace App\Interfaces\Farma;

use App\Interfaces\RepositoryInterface;
use App\Models\Utils\Filter\FilterModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductService extends RepositoryInterface
{
    public function paginate(?int $perPage = null, ?string $search = null, ?int $laboratory = null, ?int $provider = null): LengthAwarePaginator;

    public function filterProducts(FilterModel $filterModel): LengthAwarePaginator;

    public function create(array $data, array $principiosActivos = []): bool;

    public function update(int $id, array $data, ?array $principiosActivos = null): bool;

    public function getImage(int $id): ?string;

    public function getActivePrinciples(int $id): ?Collection;

    public function addActivePrinciple(int $id, int $activeId): bool;

    public function removeActivePrinciple(int $id, int $activeId): bool;

    public function options(): array;
}
