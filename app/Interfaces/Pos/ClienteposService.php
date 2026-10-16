<?php

namespace App\Interfaces\Pos;

use App\Interfaces\RepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface ClienteposService extends RepositoryInterface
{
    public function paginate(string $search = ''): LengthAwarePaginator;
}
