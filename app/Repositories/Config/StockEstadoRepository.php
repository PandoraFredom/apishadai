<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\StockEstadoService;
use App\Models\StockEstado;
use App\Repositories\Repository;

class StockEstadoRepository extends Repository implements StockEstadoService
{
    public function __construct(StockEstado $model)
    {
        parent::__construct($model);
    }
}
