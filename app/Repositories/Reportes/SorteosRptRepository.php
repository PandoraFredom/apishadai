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
        private readonly TicketService $ticketService,
        private readonly PromocionesService $promocionesService,
        private readonly ClienteService $clientesService,
        private readonly UserRepositoryInterface $userService,
        private readonly StockRepositoryInterface $stocksService
    ) {}

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function filterClientes(FilterModel $filter)
    {
        return $this->clientesService->filterAll($filter, true);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function filterSorteos(FilterModel $filter)
    {
        return $this->promocionesService->filterAll($filter, true);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function filterStocks(FilterModel $filter)
    {
        return $this->stocksService->filterAll($filter, true);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function filterTickets(FilterModel $filter)
    {
        return $this->ticketService->filterAll($filter, true);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function filterUsuarios(FilterModel $filter)
    {
        return $this->userService->filterAll($filter, true);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function getClientesList()
    {
        return $this->clientesService->paginate();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function getSorteosList()
    {
        return $this->promocionesService->paginate();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function getStocksList()
    {
        return $this->stocksService->paginate();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function getUsuariosList()
    {
        return $this->userService->paginate();
    }
}
