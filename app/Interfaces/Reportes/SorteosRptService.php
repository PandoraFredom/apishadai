<?php

namespace App\Interfaces\Reportes;

use App\Models\Utils\Filter\FilterModel;

interface SorteosRptService
{
    public function filterTickets(FilterModel $filter);

    public function filterSorteos(FilterModel $filter);

    public function filterClientes(FilterModel $filter);

    public function filterUsuarios(FilterModel $filter);

    public function filterStocks(FilterModel $filter);
}
