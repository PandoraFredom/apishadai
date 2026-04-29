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
use App\Models\Utils\Filter\FilterModel;

class SorteosReporteController extends Controller
{
    public function __construct(private SorteosRptService    $service) {}


    public function filter(TicketfilterRequest $request)
    {
        $list = $this->service->filterTickets(FilterModel::fromRequest($request->validated()));

        return $this->sendResponse(
            TicketReportResource::collection($list),
            'ok',
            200,
            true
        );
    }


    public function filter_sorteos(SorteosFilterRequest $request)
    {

        $list = $this->service->filterSorteos(FilterModel::fromRequest($request->validated()));

        return $this->sendResponse(
            SorteosRptResource::collection($list),
            'ok',
            200,
            true
        );
    }


    public function filter_clientes(ClientesFilterRequest $request)
    {
        $list = $this->service->filterClientes(FilterModel::fromRequest($request->validated()));

        return $this->sendResponse(
            TicketReportResource::collection($list),
            'ok',
            200,
            true
        );
    }


    public function filter_usuarios(UserFilterRequest $request)
    {
        $list = $this->service->filterUsuarios(FilterModel::fromRequest($request->validated()));

        return $this->sendResponse(
            TicketReportResource::collection($list),
            'ok',
            200,
            true
        );
    }

    public function filter_stocks(StocksFilterRequest $request)
    {
        $list = $this->service->filterStocks(FilterModel::fromRequest($request->validated()));

        return $this->sendResponse(
            TicketReportResource::collection($list),
            'ok',
            200,
            true
        );
    }
}
