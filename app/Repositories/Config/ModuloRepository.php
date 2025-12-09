<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\ModulosRepositoryInterface;
use App\Models\Modulos;
use App\Repositories\Repository;


class ModuloRepository extends Repository implements ModulosRepositoryInterface
{

    public function __construct(Modulos $modulo)
    {
        parent::__construct($modulo);
        $this->defaultRelations = ['estado'];
        $this->perPage = 15;
    }


}