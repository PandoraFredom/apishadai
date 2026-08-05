<?php

namespace App\Interfaces\Reportes;

use App\DTOs\Reportes\WorkLunchReportFilterDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface WorkLunchRptService
{
    public function filter(WorkLunchReportFilterDTO $filter): LengthAwarePaginator;
}
