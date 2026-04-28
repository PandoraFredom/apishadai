<?php

namespace App\Repositories\Reportes;

use  App\Http\Requests\Filters\{
    ClientesFilterRequest,
    SorteosfilterRequest,
    StocksFilterRequest,
    TicketfilterRequest,
    UserFilterRequest
};

use App\Interfaces\Clientes\ClienteService;
use App\Interfaces\Config\StockRepositoryInterface;
use App\Interfaces\Config\UserRepositoryInterface;
use App\Interfaces\Promos\PromocionesService;
use App\Interfaces\Promos\TicketService;
use App\Interfaces\Reportes\SorteosRptService;


class SorteosRptRepository implements SorteosRptService
{
    public function __construct(
        private TicketService $ticketService,
        private PromocionesService $promocionesService,
        private ClienteService $clientesService,
        private UserRepositoryInterface $userService,
        private StockRepositoryInterface $stocksService
    ) {}


    /**
     * @inheritDoc
     */
    public function filterClientes(ClientesFilterRequest $request)
    {
        return $this->clientesService->filter($request);
    }

    /**
     * @inheritDoc
     */
    public function filterSorteos(SorteosfilterRequest $request)
    {
        return $this->promocionesService->filter($request);
    }

    /**
     * @inheritDoc
     */
    public function filterStocks(StocksFilterRequest $request)
    {
        return $this->stocksService->filter($request);
    }

    /**
     * @inheritDoc
     */
    public function filterTickets(TicketfilterRequest $request)
    {
        return $this->ticketService->filter($request);
    }

    /**
     * @inheritDoc
     */
    public function filterUsuarios(UserFilterRequest $request)
    {
        return $this->userService->filter($request);
    }
}
