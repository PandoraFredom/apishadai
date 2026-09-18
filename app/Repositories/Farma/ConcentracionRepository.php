<?php

namespace App\Repositories\Farma;

use App\DTO\Farma\ConcentracionData;
use App\Interfaces\Farma\ConcentracionService;
use App\Models\Farma\Concentracion;
use App\Repositories\Repository;

class ConcentracionRepository extends Repository implements ConcentracionService
{
    public function __construct(Concentracion $model)
    {
        parent::__construct($model);
        $this->perPage = 30;
        $this->orderBy = ['id', 'DESC'];
    }

    /** @param array<string, mixed> $data */
    protected function sanitizeData(array $data): array
    {
        return ConcentracionData::sanitize($data);
    }
}
