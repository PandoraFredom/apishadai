<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\ModuloEstadoService;
use App\Models\ModuloEstados;
use App\Repositories\Repository;

class ModuloEstadoRepository extends Repository implements ModuloEstadoService
{
    public function __construct(ModuloEstados $model)
    {
        parent::__construct($model);
    }
}
