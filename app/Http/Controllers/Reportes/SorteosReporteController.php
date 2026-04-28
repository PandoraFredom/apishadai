<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Filters\ClientesFilterRequest;
use App\Http\Requests\Filters\StocksFilterRequest;
use App\Http\Requests\Filters\TicketfilterRequest;
use App\Http\Requests\Filters\UserFilterRequest;
use App\Http\Requests\Reportes\SorteosFilterRequest;
use App\Http\Resources\Reportes\SorteosRptResource;
use App\Http\Resources\Reportes\TicketReportResource;
use App\Interfaces\Reportes\SorteosRptService;

class SorteosReporteController extends Controller
{
    public function __construct(private SorteosRptService    $service) {}


    public function filter(TicketfilterRequest $request)
    {
        $list = $this->service->filterTickets($request->validated());

        return $this->sendResponse(
            TicketReportResource::collection($list),
            'ok',
            200,
            true
        );
    }


    public function filter_sorteos(SorteosFilterRequest $request)
    {
        $list = $this->service->filterSorteos($request->validated());

        return $this->sendResponse(
            SorteosRptResource::collection($list),
            'ok',
            200,
            true
        );
    }


    public function filter_clientes(ClientesFilterRequest $request)
    {
        $list = $this->service->filterClientes($request->validated());

        return $this->sendResponse(
            TicketReportResource::collection($list),
            'ok',
            200,
            true
        );
    }


    public function filter_usuarios(UserFilterRequest $request)
    {
        $list = $this->service->filterUsuarios($request->validated());

        return $this->sendResponse(
            TicketReportResource::collection($list),
            'ok',
            200,
            true
        );
    }

    public function filter_stocks(StocksFilterRequest $request)
    {
        $list = $this->service->filterStocks($request->validated());

        return $this->sendResponse(
            TicketReportResource::collection($list),
            'ok',
            200,
            true
        );
    }
}
