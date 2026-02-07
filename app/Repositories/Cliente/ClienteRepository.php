<?php

namespace App\Repositories\Cliente;

use App\Interfaces\Clientes\ClienteService;
use App\Models\Clientes;
use App\Models\Utils\Filter\FilterModel;
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

    public function filter(FilterModel $filterModel)
    {
        $conditions = [];

        foreach ($filterModel->getFilterItems() as $filterItem) {
            $key = $filterItem->getKey();
            $value = $filterItem->getValue();
            $operator = $filterItem->getOperator();
            $logicalOperator = $filterItem->getLogicalOperator();

            // Estructura: [column, operator, value, logicalOperator]
            $conditions[] = [$key, $operator, $value, $logicalOperator];
        }

        // Usar whereList con paginación - cada condición lleva su propio logicalOperator
        return $this->whereList($conditions, true);
    }

    public function activephone(int $id ): bool
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
