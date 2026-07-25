<?php

namespace App\Repositories\Proveedores;

use App\Interfaces\Proveedores\ProveedoresService;
use App\Models\Proveedores;
use App\Repositories\Repository;

class ProveedoresRepository extends Repository implements ProveedoresService
{
    public function __construct(Proveedores $model)
    {
        parent::__construct($model);
        $this->perPage = 30;
        $this->orderBy = ['id', 'DESC'];
    }



    /**
     * @inheritDoc
     */
    public function getImage(int $id)
    {

        $proveedor = $this->findById($id);

        if (!$proveedor) {
            return null;
        }

        return $proveedor->imagen;
    }
}
