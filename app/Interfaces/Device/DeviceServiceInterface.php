<?php

namespace App\Interfaces\Device;

use App\DTOs\DeviceDTO;
use App\Http\Resources\Device\DeviceCollection;
use App\Models\Device;
use Illuminate\Pagination\LengthAwarePaginator;

interface DeviceServiceInterface
{
    public function getAllDevices(bool $withRelations = true): DeviceCollection;
    public function getPaginatedDevices(int $perPage = 15, bool $withRelations = true): LengthAwarePaginator;
    public function getDeviceById(int $id, bool $withRelations = true): ?Device;
    public function createDevice(DeviceDTO $dto): bool;
    public function updateDevice(int $id, DeviceDTO $dto): bool;
    public function deleteDevice(int $id): bool;
    public function getDevicesByStock(int $stockId): DeviceCollection;
    public function getDevicesByEstado(int $estadoId): DeviceCollection;
}