<?php

namespace App\Interfaces\Farma;

use App\Interfaces\RepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface PrincipalActivoService extends RepositoryInterface
{
    public function paginate(?int $perPage = null): LengthAwarePaginator;
}
