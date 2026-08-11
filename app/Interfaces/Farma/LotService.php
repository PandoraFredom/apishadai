<?php

namespace App\Interfaces\Farma;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface LotService
{
    public function paginateProducts(int $perPage = 30, ?string $search = null): LengthAwarePaginator;
    public function productLots(int $productId): Collection;
}
