<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\StockRepositoryInterface;
use App\Models\Stocks;
use App\Repositories\Repository;

class StockRepository extends Repository implements StockRepositoryInterface
{
    public function __construct(Stocks $model)
    {
        parent::__construct($model);
    }
}
