<?php

namespace App\Repositories\Reportes;

use App\DTOs\Reportes\WorkLunchReportFilterDTO;
use App\Interfaces\Reportes\WorkLunchRptService;
use App\Models\WorkLunch;
use Illuminate\Pagination\LengthAwarePaginator;

class WorkLunchRptRepository implements WorkLunchRptService
{
    public function __construct(
        private readonly WorkLunch $model,
    ) {}

    public function filter(WorkLunchReportFilterDTO $filter): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['User', 'Device.Stock'])
            ->where('usuario', $filter->usuario)
            ->whereBetween('work_date', [$filter->desde, $filter->hasta])
            ->orderByDesc('work_date')
            ->orderByDesc('wkstart_time')
            ->paginate(30);
    }
}
