<?php

namespace App\Repositories\Farma;

use App\DTO\Farma\ProdUnidadData;
use App\Interfaces\Farma\ProdUnidadService;
use App\Models\Farma\ProdUnidad;
use App\Repositories\Repository;

class ProdUnidadRepository extends Repository implements ProdUnidadService
{
    public function __construct(ProdUnidad $model)
    {
        parent::__construct($model);
        $this->perPage = 30;
        $this->orderBy = ['id', 'DESC'];
    }

    /** @param array<string, mixed> $data */
    protected function sanitizeData(array $data): array
    {
        return ProdUnidadData::sanitize($data);
    }
}
