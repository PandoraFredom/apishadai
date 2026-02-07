<?php


namespace App\Interfaces\Ubicacion;

use App\Interfaces\RepositoryInterface;

interface MunicipiosService extends RepositoryInterface
{
    public function getByDepartamento(int $departamentoId);
}
