<?php

namespace App\Interfaces\Proveedores;

use App\Interfaces\RepositoryInterface;

interface ProveedoresService extends RepositoryInterface
{

    //get images from proveedor
    public function getImage(int $id);
}
