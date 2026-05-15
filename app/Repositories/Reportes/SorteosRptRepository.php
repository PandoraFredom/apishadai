<?php

namespace App\Repositories\Reportes;

use App\Interfaces\Clientes\ClienteService;
use App\Interfaces\Config\StockRepositoryInterface;
use App\Interfaces\Config\UserRepositoryInterface;
use App\Interfaces\Promos\PromocionesService;
use App\Interfaces\Promos\TicketService;
use App\Interfaces\Reportes\SorteosRptService;
use App\Models\Utils\Filter\FilterModel;

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
    public function filterClientes(FilterModel $filter)
    {
        return $this->clientesService->filterAll($filter);
    }

    /**
     * @inheritDoc
     */
    public function filterSorteos(FilterModel $filter)
    {
        return $this->promocionesService->filterAll($filter);
    }

    /**
     * @inheritDoc
     */
    public function filterStocks(FilterModel $filter)
    {
        return $this->stocksService->filterAll($filter);
    }

    /**
     * @inheritDoc
     */
    public function filterTickets(FilterModel $filter)
    {
        return $this->ticketService->filterAll($filter);
    }

    /**
     * @inheritDoc
     */
    public function filterUsuarios(FilterModel $filter)
    {
        return $this->userService->filterAll($filter);
    }

    /**
     * @inheritDoc
     */
    public function getClientesList() {
        return $this->clientesService->paginate();
    }

    /**
     * @inheritDoc
     */
    public function getSorteosList() {
        return $this->promocionesService->paginate();
    }

    /**
     * @inheritDoc
     */
    public function getStocksList() {
        return $this->stocksService->paginate();
    }

    /**
     * @inheritDoc
     */
    public function getUsuariosList() {
        return $this->userService->paginate();
    }
}
