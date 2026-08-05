<?php

namespace App\Http\Controllers\Reportes;

use App\DTOs\Reportes\WorkLunchReportFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\WorkLunchFilterRequest;
use App\Http\Resources\Reportes\WorkLunchRptResource;
use App\Interfaces\Reportes\WorkLunchRptService;
use Illuminate\Http\JsonResponse;

class RptWorkLunchController extends Controller
{
    public function __construct(
        private readonly WorkLunchRptService $service,
    ) {}

    public function filter(WorkLunchFilterRequest $request): JsonResponse
    {
        $filter = WorkLunchReportFilterDTO::fromRequest($request->validated());
        $report = $this->service->filter($filter);

        return $this->sendResponse(
            WorkLunchRptResource::collection($report),
            'Reporte de jornada laboral consultado.',
            200,
            true,
        );
    }
}
