<?php

namespace App\Interfaces\Reportes;

use App\Http\Requests\Filters\ClientesFilterRequest;
use App\Http\Requests\Filters\SorteosfilterRequest;
use App\Http\Requests\Filters\StocksFilterRequest;
use App\Http\Requests\Filters\TicketfilterRequest;
use App\Http\Requests\Filters\UserFilterRequest;

interface SorteosRptService
{
    public function filterTickets(TicketfilterRequest $request);

    public function filterSorteos(SorteosfilterRequest $request);

    public function filterClientes(ClientesFilterRequest $request);

    public function filterUsuarios(UserFilterRequest $request);

    public function filterStocks(StocksFilterRequest $request);
}
