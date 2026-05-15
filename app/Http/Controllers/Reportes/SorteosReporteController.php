<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Filters\ClientesFilterRequest;
use App\Http\Requests\Filters\StocksFilterRequest;
use App\Http\Requests\Filters\TicketfilterRequest;
use App\Http\Requests\Filters\UserFilterRequest;
use App\Http\Requests\Reportes\SorteosFilterRequest;
use App\Http\Resources\Clientes\ClientesResource;
use App\Http\Resources\Reportes\ClientesRptResource;
use App\Http\Resources\Reportes\SorteosRptResource;
use App\Http\Resources\Reportes\StockRptResource;
use App\Http\Resources\Reportes\TicketReportResource;
use App\Http\Resources\Reportes\UsuarioRptResource;
use App\Http\Resources\Stock\StocksResource;
use App\Http\Resources\UserResource;
use App\Interfaces\Reportes\SorteosRptService;
use App\Models\Utils\Filter\FilterModel;
use Illuminate\Support\Facades\Log;

class SorteosReporteController extends Controller
{
    public function __construct(private SorteosRptService    $service) {}


    public function filter(TicketfilterRequest $request)
    {
        $list = $this->service->filterTickets($request->toFilterModel());

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

    public function get_sorteos_list()
    {
        $list = $this->service->getSorteosList();

        return $this->sendResponse(
            SorteosRptResource::collection($list),
            'ok',
            200,
            true
        );
    }


    public function filter_clientes(ClientesFilterRequest $request)
    {
        $list = $this->service->filterClientes($request->toFilterModel());

        return $this->sendResponse(
            ClientesRptResource::collection($list),
            'ok',
            200,
            false
        );
    }
    public function get_clientes_list()
    {
        $list = $this->service->getClientesList();

        return $this->sendResponse(
            ClientesRptResource::collection($list),
            'ok',
            200,
            true
        );
    }


    public function filter_usuarios(UserFilterRequest $request)
    {
        $list = $this->service->filterUsuarios($request->toFilterModel());

        return $this->sendResponse(
            UsuarioRptResource::collection($list),
            'ok',
            200,
            true
        );
    }

    public function get_usuarios_list()
    {
        $list = $this->service->getUsuariosList();

        return $this->sendResponse(
            UsuarioRptResource::collection($list),
            'ok',
            200,
            true
        );
    }

    public function filter_stocks(StocksFilterRequest $request)
    {
        $list = $this->service->filterStocks($request->toFilterModel());

        return $this->sendResponse(
            StockRptResource::collection($list),
            'ok',
            200,
            true
        );
    }

    public function get_stocks_list()
    {
        $list = $this->service->getStocksList();

        return $this->sendResponse(
            StocksResource::collection($list),
            'ok',
            200,
            true
        );
    }
}
