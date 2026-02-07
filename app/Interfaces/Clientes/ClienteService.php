<?php

namespace App\Interfaces\Clientes;

use App\Interfaces\RepositoryInterface;
use App\Models\Utils\Filter\FilterModel;

interface ClienteService extends RepositoryInterface
{
    public function filter(FilterModel $filterModel);

    public function activephone(int $id): bool;
}
