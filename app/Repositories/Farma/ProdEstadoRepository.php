<?php

namespace App\Repositories\Farma;

use App\DTO\Farma\ProdEstadoData;
use App\Interfaces\Farma\ProdEstadoService;
use App\Models\Farma\ProdEstado;
use App\Repositories\Repository;

class ProdEstadoRepository extends Repository implements ProdEstadoService
{
    public function __construct(ProdEstado $model)
    {
        parent::__construct($model);
        $this->perPage = 30;
        $this->orderBy = ['id', 'DESC'];
    }

    /** @param array<string, mixed> $data */
    protected function sanitizeData(array $data): array
    {
        return ProdEstadoData::sanitize($data);
    }

    protected function filterEmptyData(array $data): array
    {
        // La descripción nullable puede limpiarse explícitamente.
        return $data;
    }
}
