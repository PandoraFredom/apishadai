<?php

namespace App\Http\Controllers;

use App\DTOs\DeviceDTO;
use App\Http\Requests\DeviceRequest;
use App\Http\Resources\Device\DeviceCollection;
use App\Http\Resources\DeviceResource;
use App\Interfaces\Device\DeviceServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceController extends Controller
{
    public function __construct(
        protected DeviceServiceInterface $deviceService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);

            $paginator = $this->deviceService->getPaginatedDevices($perPage);

            $devices = DeviceCollection::make($paginator);

            return $this->sendResponse(
                $devices,
                'ok',
                200,
                true
            );

        } catch (\Exception $e) {
            Log::error('Error en DeviceController@index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('Error al obtener los dispositivos: '.$e->getMessage(), null, 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $device = $this->deviceService->getDeviceById($id);

            if (!$device) {
                return $this->sendError('Dispositivo no encontrado', null, 404);
            }

            return $this->sendResponse(
                DeviceResource::make($device),
                'Dispositivo encontrado correctamente',
                200
            );
        } catch (\Exception $e) {
            Log::error('Error en DeviceController@show', [
                'device_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Error al buscar el dispositivo', null, 500);
        }
    }

    public function store(DeviceRequest $request): JsonResponse
    {
        try {
            $dto = DeviceDTO::onCreate($request->validated());
            $created = $this->deviceService->createDevice($dto);

            if (!$created) {
                return $this->sendError(
                    'No se pudo crear el dispositivo.',
                    false,
                    422
                );
            }

            return $this->sendResponse(true, 'Dispositivo creado correctamente', 201);
        } catch (\Exception $e) {

            Log::error('Error en DeviceController@store', [
                'request_data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('Error al crear el dispositivo', false, 500);
        }
    }

    public function update(DeviceRequest $request): JsonResponse
    {
        try {
            $dto = DeviceDTO::onUpdate($request->validated());
            $updated = $this->deviceService->updateDevice($dto->id, $dto);

            if (!$updated) {
                return $this->sendError(
                    'No se pudo actualizar el dispositivo.',
                    false,
                    404 
                );
            }

            return $this->sendResponse(true, 'Dispositivo actualizado correctamente', 200);
        } catch (\Exception $e) {
            Log::error('Error en DeviceController@update', [
                'device_id' => $dto->id,
                'request_data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Error al actualizar el dispositivo:'.$e->getMessage(), null, 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->deviceService->deleteDevice($id);

            if (!$deleted) {
                return $this->sendError(
                    'Dispositivo no encontrado o no se pudo eliminar',
                    false,
                    404
                );
            }

            return $this->sendResponse(true, 'Dispositivo eliminado correctamente', 200);
        } catch (\Exception $e) {
            Log::error('Error en DeviceController@destroy', [
                'device_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Error al eliminar el dispositivo', null, 500);
        }
    }

    public function byStock(int $stockId): JsonResponse
    {
        try {
            $devices = $this->deviceService->getDevicesByStock($stockId);
            return $this->sendResponse(
                $devices,
                'Dispositivos por stock obtenidos correctamente',
                200
            );
        } catch (\Exception $e) {
            Log::error('Error en DeviceController@byStock', [
                'stock_id' => $stockId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Error al buscar dispositivos por stock', null, 500);
        }
    }

    public function byEstado(int $estadoId): JsonResponse
    {
        try {
            $devices = $this->deviceService->getDevicesByEstado($estadoId);
            return $this->sendResponse(
                $devices,
                'Dispositivos por estado obtenidos correctamente',
                200
            );
        } catch (\Exception $e) {
            Log::error('Error en DeviceController@byEstado', [
                'estado_id' => $estadoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Error al buscar dispositivos por estado', null, 500);
        }
    }

}