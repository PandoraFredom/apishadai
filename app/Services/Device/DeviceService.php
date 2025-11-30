<?php

namespace App\Services\Device;

use App\DTOs\DeviceDTO;
use App\Http\Resources\Device\DeviceCollection;
use App\Interfaces\Device\DeviceRepositoryInterface;
use App\Interfaces\Device\DeviceServiceInterface;


use App\Models\Device;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DeviceService implements DeviceServiceInterface
{
    protected array $defaultRelations = ['Estado', 'Stock'];

    public function __construct(
        protected DeviceRepositoryInterface $deviceRepository
    ) {
    }

    public function getAllDevices(bool $withRelations = true): DeviceCollection
    {
        try {
            $relations = $withRelations ? $this->defaultRelations : [];
            $devices = $this->deviceRepository->getAll($relations);
            return new DeviceCollection($devices);
        } catch (Exception $e) {
            Log::error('Error al obtener dispositivos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getPaginatedDevices(int $perPage = 15, bool $withRelations = true): LengthAwarePaginator
    {
        try {
            $relations = $withRelations ? $this->defaultRelations : [];
            // Retornamos directamente el LengthAwarePaginator
            return $this->deviceRepository->paginate($perPage, $relations);
        } catch (Exception $e) {
            Log::error('Error al paginar dispositivos', [
                'per_page' => $perPage,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getDeviceById(int $id, bool $withRelations = true): ?Device
    {
        try {
            $relations = $withRelations ? $this->defaultRelations : [];
            return $this->deviceRepository->findById($id, $relations);
        } catch (Exception $e) {
            Log::error('Error al buscar dispositivo por ID', [
                'device_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }



    public function createDevice(DeviceDTO $dto): bool
    {
        DB::beginTransaction();
        try {
            $existingDevice = $this->deviceRepository->findByDisplayname($dto->displayname);
            if ($existingDevice) {
                Log::warning('Intento de crear dispositivo con displayname duplicado', [
                    'displayname' => $dto->displayname
                ]);
                DB::rollBack();
                return false;
            }

            $device = $this->deviceRepository->create($dto->toArray());
            DB::commit();


            if ($device === null) {
                DB::rollBack();
                return false;
            }
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear dispositivo', [
                'dto' => $dto->toArray(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function updateDevice(int $id, DeviceDTO $dto): bool
    {
        DB::beginTransaction();
        try {
            if (!$this->deviceRepository->exists($id)) {
                Log::warning('Intento de actualizar dispositivo inexistente', [
                    'device_id' => $id
                ]);
                DB::rollBack();
                return false;
            }

            $existingDevice = $this->deviceRepository->findByDisplayname($dto->displayname, $id);
            if ($existingDevice) {
                Log::warning('Intento de actualizar con displayname duplicado', [
                    'device_id' => $id,
                    'displayname' => $dto->displayname
                ]);
                DB::rollBack();
                return false;
            }

            $updated = $this->deviceRepository->update($id, $dto->toArray());
            if (!$updated) {
                DB::rollBack();
                return false;
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar dispositivo', [
                'device_id' => $id,
                'dto' => $dto->toArray(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function deleteDevice(int $id): bool
    {
        DB::beginTransaction();
        try {
            if (!$this->deviceRepository->exists($id)) {
                Log::warning('Intento de eliminar dispositivo inexistente', [
                    'device_id' => $id
                ]);
                DB::rollBack();
                return false;
            }

            $deleted = $this->deviceRepository->delete($id);
            if (!$deleted) {
                DB::rollBack();
                return false;
            }

            DB::commit();
            Log::info('Dispositivo eliminado exitosamente', [
                'device_id' => $id
            ]);
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar dispositivo', [
                'device_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getDevicesByStock(int $stockId): DeviceCollection
    {
        try {
            $devices = $this->deviceRepository->findByStock($stockId, $this->defaultRelations);
            return new DeviceCollection($devices);
        } catch (Exception $e) {
            Log::error('Error al buscar dispositivos por stock', [
                'stock_id' => $stockId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getDevicesByEstado(int $estadoId): DeviceCollection
    {
        try {
            $devices = $this->deviceRepository->findByEstado($estadoId, $this->defaultRelations);
            return new DeviceCollection($devices);
        } catch (Exception $e) {
            Log::error('Error al buscar dispositivos por estado', [
                'estado_id' => $estadoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}