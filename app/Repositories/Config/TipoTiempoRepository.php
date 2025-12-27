<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\TipoTiempoService;
use App\Models\TipoTiempo;
use App\Repositories\Repository;

class TipoTiempoRepository extends Repository implements TipoTiempoService
{
    public function __construct(TipoTiempo $model)
    {
        parent::__construct($model);
    }
}
