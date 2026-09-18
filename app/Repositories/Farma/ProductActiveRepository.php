<?php

namespace App\Repositories\Farma;

use App\Interfaces\Farma\ProductActiveService;
use App\Models\Farma\ProdActivo;
use App\Repositories\Repository;

class ProductActiveRepository extends Repository implements ProductActiveService
{
    public function __construct(ProdActivo $model)
    {
        parent::__construct($model);
        $this->perPage = 30;
        $this->orderBy = ['id', 'DESC'];
        $this->defaultRelations = ['productoDetalle:id,codigo,codigobar,descripcion', 'principioActivo.concentracionDetalle'];
    }
}
