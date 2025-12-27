<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\DeviceEstadoService;
use App\Models\DeviceEstado;
use App\Repositories\Repository;

class DeviceEstadoRepository extends Repository implements DeviceEstadoService
{
    public function __construct(DeviceEstado $model)
    {
        parent::__construct($model);
    }
}
