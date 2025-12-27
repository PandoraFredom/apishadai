<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\UserEstadoRepositoryInterface;
use App\Models\UsuarioEstado;
use App\Repositories\Repository;

class UserEstadoRepository extends Repository implements UserEstadoRepositoryInterface
{
    public function __construct(UsuarioEstado $model)
    {
        parent::__construct($model);
    }
}
