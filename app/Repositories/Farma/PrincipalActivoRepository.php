<?php

namespace App\Repositories\Farma;

use App\DTO\Farma\PrincipalActivoData;
use App\Interfaces\Farma\PrincipalActivoService;
use App\Models\Farma\PrincipalActivo;
use App\Repositories\Repository;
use Illuminate\Pagination\LengthAwarePaginator;

class PrincipalActivoRepository extends Repository implements PrincipalActivoService
{
    public function __construct(PrincipalActivo $model)
    {
        parent::__construct($model);
        $this->perPage = 30;
        $this->orderBy = ['id', 'DESC'];
        $this->defaultRelations = ['concentracionDetalle'];
    }

    public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        if ($perPage !== null) {
            $this->setPerPage($perPage);
        }

        return parent::paginate();
    }

    /** @param array<string, mixed> $data */
    protected function sanitizeData(array $data): array
    {
        return PrincipalActivoData::sanitize($data);
    }
}
