<?php

namespace App\Repositories\Config;

use App\Http\Requests\Util\FilterRequest;
use App\Interfaces\Config\StockEstadoService;
use App\Interfaces\Config\StockRepositoryInterface;
use App\Models\Stocks;
use App\Repositories\Repository;
use Illuminate\Database\Eloquent\Collection;

class StockRepository extends Repository implements StockRepositoryInterface
{
    public function __construct(
        Stocks $model,
        private StockEstadoService $stockEstadoService
    ) {
        parent::__construct($model);
        $this->defaultRelations = ['estado'];
        $this->perPage = 30;
    }

    public function get_estadosList(): Collection
    {
        return $this->stockEstadoService->getAll();
    }

}
