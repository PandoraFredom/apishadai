<?php

namespace App\Repositories\Farma;

use App\DTO\Farma\PrincipalActivoData;
use App\Interfaces\Farma\PrincipalActivoService;
use App\Models\Farma\PrincipalActivo;
use App\Repositories\Repository;

class PrincipalActivoRepository extends Repository implements PrincipalActivoService
{
    public function __construct(PrincipalActivo $model)
    {
        parent::__construct($model);
        $this->perPage = 30;
        $this->orderBy = ['id', 'DESC'];
        $this->defaultRelations = ['concentracionDetalle'];
    }

    /** @param array<string, mixed> $data */
    protected function sanitizeData(array $data): array
    {
        return PrincipalActivoData::sanitize($data);
    }
}
