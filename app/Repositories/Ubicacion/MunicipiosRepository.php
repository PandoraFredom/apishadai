<?php

namespace App\Repositories\Ubicacion;

use App\Interfaces\Ubicacion\MunicipiosService;
use App\Models\Municipios;
use App\Repositories\Repository;

class MunicipiosRepository extends Repository implements MunicipiosService
{
    public function __construct(Municipios $model)
    {
        parent::__construct($model);
        $this->orderBy = ['nombre', 'ASC'];
        $this->defaultRelations = ['departamento'];
    }



    public function getByDepartamento(int $departamentoId)
    {
        return $this->whereList(
            conditions: [
                ['departamento', '=', $departamentoId]
            ]
        );
    }
}
