<?php

namespace App\Http\Controllers\Reportes;

use App\DTO\Reportes\WorkLunchReportFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\WorkLunchFilterRequest;
use App\Http\Resources\HorasLabResource;
use App\Http\Resources\Reportes\UsuarioRptResource;
use App\Http\Resources\Reportes\WorkLunchRptResource;
use App\Interfaces\Config\HorasLabService;
use App\Interfaces\Reportes\WorkLunchRptService;
use App\Models\HorasLab;
use Illuminate\Http\JsonResponse;

class RptWorkLunchController extends Controller
{
    public function __construct(
        private readonly WorkLunchRptService $service,
        private readonly HorasLabService $horasLabService,
    ) {}

    public function filter(WorkLunchFilterRequest $request): JsonResponse
    {
        $filter = WorkLunchReportFilterDTO::fromRequest($request->validated());
        $report = $this->service->filter($filter);

        return $this->sendResponse(
            WorkLunchRptResource::collection($report),
            'ok',
            200,
            false,
        );
    }

    public function getUsuariosList(): JsonResponse
    {
        return $this->sendResponse(
            UsuarioRptResource::collection($this->service->getUsuariosList()),
            'ok',
            200,
            false,
        );
    }

    public function getHorasLabByUser(int $userId): JsonResponse
    {
        $schedule = $this->horasLabService->findByUser($userId);

        if (! $schedule) {
            return $this->sendError(
                'empty',
                HorasLabResource::make(new HorasLab([
                    'usuario' => $userId,
                    'horas_lab' => $schedule?->horas_lab ?? 0,
                    'horas_lunch' => $schedule?->horas_lunch ?? 0,
                ])),
                200,
            );
        }

        return $this->sendResponse(
            HorasLabResource::make($schedule),
            'ok',
        );
    }
}
