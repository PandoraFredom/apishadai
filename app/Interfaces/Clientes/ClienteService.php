<?php

namespace App\Interfaces\Clientes;

use App\Http\Requests\Util\FilterRequest;
use App\Interfaces\RepositoryInterface;
use App\Models\Utils\Filter\FilterModel;

interface ClienteService extends RepositoryInterface
{
    public function filter(FilterRequest $request);

    public function activephone(int $id): bool;
}
