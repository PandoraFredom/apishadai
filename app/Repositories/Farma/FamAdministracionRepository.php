<?php

namespace App\Repositories\Farma;

use App\DTO\Farma\FamAdministracionData;
use App\Interfaces\Farma\FamAdministracionService;
use App\Models\Farma\FamAdministracion;
use App\Repositories\Repository;

class FamAdministracionRepository extends Repository implements FamAdministracionService
{
    public function __construct(FamAdministracion $model)
    {
        parent::__construct($model);
        $this->perPage = 30;
        $this->orderBy = ['id', 'DESC'];
    }

    /** @param array<string, mixed> $data */
    protected function sanitizeData(array $data): array
    {
        return FamAdministracionData::sanitize($data);
    }
}
