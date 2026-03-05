<?php

namespace App\Http\Controllers\API;

use App\DTOs\StockEstadoDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockEstadoRequest;
use App\Http\Requests\StockEstadoUpdateRequest;
use App\Http\Resources\Stock\StockEstadoResource;
use App\Interfaces\Config\StockEstadoService;
use Illuminate\Http\JsonResponse;

class StocksEstadoController extends Controller
{
    public function __construct(private StockEstadoService $service) {}

    public function index(): JsonResponse
    {
        try {
            $list = $this->service->paginate();
            return $this->sendResponse(StockEstadoResource::collection($list), 'ok', 200, true);
        } catch (\Throwable $th) {
            $this->logError('StocksEstadoController index', $th);
            return $this->sendError('Error al listar estados de stock.', null, 500);
        }
    }

    public function store(StockEstadoRequest $request): JsonResponse
    {
        try {
            $dto = StockEstadoDTO::fromRequest($request->validated());
            $created = $this->service->create($dto->toArray());

            if (!$created) {
                return $this->sendError('Error al crear estado de stock.', false, 422);
            }

            return $this->sendResponse(true, 'Estado de stock creado correctamente', 201);
        } catch (\Throwable $th) {
            $this->logError('StocksEstadoController store', $th);
            return $this->sendError('Error al crear estado de stock.', false, 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = $this->service->findById($id);
            if (!$item) {
                return $this->sendError('Estado de stock no encontrado', null, 404);
            }

            return $this->sendResponse(StockEstadoResource::make($item), 'ok');
        } catch (\Throwable $th) {
            $this->logError('StocksEstadoController show', $th);
            return $this->sendError('Error al buscar estado de stock.', null, 500);
        }
    }

    public function update(StockEstadoUpdateRequest $request): JsonResponse
    {
        try {
            $dto = StockEstadoDTO::fromUpdateRequest($request->validated());
            $updated = $this->service->update($dto->id, $dto->toUpdateArray());

            if (!$updated) {
                return $this->sendError('No se pudo actualizar el estado de stock.', false, 404);
            }

            return $this->sendResponse(true, 'Estado de stock actualizado correctamente', 200);
        } catch (\Throwable $th) {
            $this->logError('StocksEstadoController update', $th);
            return $this->sendError('Error al actualizar estado de stock.', null, 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->service->delete($id);

            if (!$deleted) {
                return $this->sendError('Estado de stock no disponible para eliminar.', false, 404);
            }

            return $this->sendResponse(true, 'Estado de stock eliminado correctamente', 200);
        } catch (\Throwable $th) {
            $this->logError('StocksEstadoController destroy', $th);
            return $this->sendError('Error al eliminar estado de stock.', null, 500);
        }
    }
}
