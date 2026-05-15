<?php

namespace App\Interfaces\Reportes;

use App\Models\Utils\Filter\FilterModel;

interface SorteosRptService
{
    public function filterTickets(FilterModel $filter);

    public function filterSorteos(FilterModel $filter);

    public function getSorteosList();

    public function filterClientes(FilterModel $filter);

    public function getClientesList();

    public function filterUsuarios(FilterModel $filter);

    public function getUsuariosList();

    public function filterStocks(FilterModel $filter);

    public function getStocksList();
}
