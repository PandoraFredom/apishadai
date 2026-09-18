<?php

namespace App\Repositories\Farma;

use App\DTO\Farma\FamPresentacionData;
use App\Interfaces\Farma\FamPresentacionService;
use App\Models\Farma\FamPresentacion;
use App\Repositories\Repository;

class FamPresentacionRepository extends Repository implements FamPresentacionService
{
    public function __construct(FamPresentacion $model)
    {
        parent::__construct($model);
        $this->perPage = 30;
        $this->orderBy = ['id', 'DESC'];
    }

    /** @param array<string, mixed> $data */
    protected function sanitizeData(array $data): array
    {
        return FamPresentacionData::sanitize($data);
    }
}
