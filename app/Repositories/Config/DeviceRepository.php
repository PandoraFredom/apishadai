<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\DeviceEstadoService;
use App\Interfaces\Config\StockRepositoryInterface;
use App\Interfaces\Config\DeviceService;
use App\Models\Device;
use App\Repositories\Repository;
use Illuminate\Database\Eloquent\Collection;

class DeviceRepository  extends Repository implements DeviceService
{


    public function __construct(
        Device $model,
        private StockRepositoryInterface $stockService,
        private DeviceEstadoService $deviceEstadoService
    ) {
        parent::__construct($model);
        $this->defaultRelations = ['estado', 'stock'];
        $this->perPage = 30;
    }

    public function get_estadosList(): Collection
    {
        return $this->deviceEstadoService->getAll();
    }
    public function get_stocksList(): Collection
    {
        return $this->stockService->getAll();
    }
}
