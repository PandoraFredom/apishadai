<?php

namespace Tests\Feature;

use App\Interfaces\Clientes\ClienteService;
use App\Interfaces\Config\StockRepositoryInterface;
use App\Interfaces\Config\UserRepositoryInterface;
use App\Interfaces\Promos\PromocionesService;
use App\Interfaces\Promos\TicketService;
use App\Models\Utils\Filter\FilterModel;
use App\Repositories\Reportes\SorteosRptRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class RptSorteosRepositoryTest extends TestCase
{
    public function test_report_filters_are_paginated_to_avoid_unbounded_results(): void
    {
        $filter = new FilterModel;
        $paginator = new LengthAwarePaginator([], 0, 20);
        $tickets = Mockery::mock(TicketService::class);
        $draws = Mockery::mock(PromocionesService::class);
        $clients = Mockery::mock(ClienteService::class);
        $users = Mockery::mock(UserRepositoryInterface::class);
        $stocks = Mockery::mock(StockRepositoryInterface::class);

        foreach ([$tickets, $draws, $clients, $users, $stocks] as $service) {
            $service->shouldReceive('filterAll')
                ->once()
                ->with($filter, true)
                ->andReturn($paginator);
        }

        $repository = new SorteosRptRepository($tickets, $draws, $clients, $users, $stocks);

        $this->assertSame($paginator, $repository->filterTickets($filter));
        $this->assertSame($paginator, $repository->filterSorteos($filter));
        $this->assertSame($paginator, $repository->filterClientes($filter));
        $this->assertSame($paginator, $repository->filterUsuarios($filter));
        $this->assertSame($paginator, $repository->filterStocks($filter));
    }
}
