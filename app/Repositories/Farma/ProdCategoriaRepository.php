<?php

namespace App\Repositories\Farma;

use App\DTO\Farma\ProdCategoriaData;
use App\Interfaces\Farma\ProdCategoriaService;
use App\Models\Farma\ProdCategoria;
use App\Repositories\Repository;

class ProdCategoriaRepository extends Repository implements ProdCategoriaService
{
    public function __construct(ProdCategoria $model)
    {
        parent::__construct($model);
        $this->perPage = 30;
        $this->orderBy = ['id', 'DESC'];
    }

    /** @param array<string, mixed> $data */
    protected function sanitizeData(array $data): array
    {
        return ProdCategoriaData::sanitize($data);
    }
}
