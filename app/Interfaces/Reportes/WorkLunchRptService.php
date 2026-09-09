<?php

namespace App\Interfaces\Reportes;

use App\DTO\Reportes\WorkLunchReportFilterDTO;

use Illuminate\Support\Collection;

interface WorkLunchRptService
{
    public function filter(WorkLunchReportFilterDTO $filter): Collection;

    public function getUsuariosList(): Collection;
}
