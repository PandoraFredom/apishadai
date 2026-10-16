<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\DeviceEstadoService;
use App\Interfaces\Config\DeviceService;
use App\Interfaces\Config\StockRepositoryInterface;
use App\Models\Device;
use App\Repositories\Repository;
use Illuminate\Database\Eloquent\Collection;

class DeviceRepository extends Repository implements DeviceService
{
    public function __construct(
        Device $model,
        private readonly StockRepositoryInterface $stockService,
        private readonly DeviceEstadoService $deviceEstadoService
    ) {
        parent::__construct($model);
        $this->defaultRelations = ['estado', 'stock'];
        $this->perPage = 30;
    }

    public function get_estadosList(): Collection
    {
        return $this->deviceEstadoService->getAll();
    }

    protected function filterEmptyData(array $data): array
    {
        $filtered = parent::filterEmptyData($data);
        if (array_key_exists('hq_pairing_uuid', $data)) {
            $filtered['hq_pairing_uuid'] = $data['hq_pairing_uuid'];
        }

        return $filtered;
    }

    public function get_stocksList(): Collection
    {
        return $this->stockService->getAll();
    }
}
