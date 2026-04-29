<?php

namespace App\Repositories\Cliente;

use App\Http\Requests\Util\FilterRequest;
use App\Interfaces\Clientes\ClienteService;
use App\Models\Clientes;
use App\Repositories\Repository;

class ClienteRepository extends Repository implements ClienteService
{
    public function __construct(Clientes $model)
    {
        parent::__construct($model);
        $this->defaultRelations = ['departamento', 'municipio'];
        $this->orderBy = ['id', 'DESC'];
        $this->perPage = 12;
    }

    public function activephone(int $id): bool
    {
        $phoneRecord = $this->whereFirst(
            [
                ['id', '=', $id],
                ['phone_updated_at', '!=', null],
                ['phone_updated_at', '>', now()->subMonth()]
            ]
        );

        return $phoneRecord !== null;
    }
}
