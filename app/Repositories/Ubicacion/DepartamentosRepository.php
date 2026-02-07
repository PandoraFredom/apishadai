<?php



namespace App\Repositories\Ubicacion;
use App\Interfaces\Ubicacion\DepartamentosService;
use App\Models\Departamento;
use App\Repositories\Repository;

class DepartamentosRepository extends Repository implements DepartamentosService
{
    public function __construct(Departamento $model)
    {
        parent::__construct($model);
        $this->orderBy = ['nombre', 'ASC'];
    }
}
