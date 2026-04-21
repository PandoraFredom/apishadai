<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\SorteosFilterRequest;
use App\Http\Resources\Reportes\TicketReportResource;
use App\Interfaces\Promos\TicketService;


class SorteosReporteController extends Controller
{
    public function __construct(private TicketService    $service) {}


    public function filter(SorteosFilterRequest $request)
    {
        $list = $this->service->filter($request->validated());

        return $this->sendResponse(
            TicketReportResource::collection($list),
            'ok',
            200,
            true
        );
    }
}
