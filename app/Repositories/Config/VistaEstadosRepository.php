<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\VistaEstadosService;
use App\Models\VistaEstados;
use App\Repositories\Repository;

class VistaEstadosRepository extends Repository implements VistaEstadosService
{
    public function __construct(
        VistaEstados $model
    ) {
        parent::__construct($model);
    }
}
