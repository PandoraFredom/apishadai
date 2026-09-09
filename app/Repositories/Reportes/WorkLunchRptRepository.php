<?php

namespace App\Repositories\Reportes;

use App\DTO\Reportes\WorkLunchReportFilterDTO;
use App\Interfaces\Config\UserRepositoryInterface;
use App\Interfaces\Reportes\WorkLunchRptService;
use App\Interfaces\WorkLunch\WorkLunchService;
use App\Models\Utils\Filter\FilterItem;
use App\Models\Utils\Filter\FilterModel;
use Illuminate\Support\Collection;

class WorkLunchRptRepository implements WorkLunchRptService
{
    public function __construct(
        private readonly WorkLunchService $service,
        private readonly UserRepositoryInterface $userService,
    ) {}

    #[\Override]
    public function filter(WorkLunchReportFilterDTO $filter): Collection
    {
        $filterModel = new FilterModel(
            'worklunch_report',
            new FilterItem('usuario', $filter->usuario, '='),
            new FilterItem('work_date', [$filter->desde, $filter->hasta], 'BETWEEN'),
        );

        return $this->service->filterAll($filterModel, false);
    }

    #[\Override]
    public function getUsuariosList(): Collection
    {
        return $this->userService->getAll();
    }
}
