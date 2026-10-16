<?php

namespace App\Http\Controllers\API\Pos;

use App\DTO\ClienteposDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\ClienteposListRequest;
use App\Http\Requests\Pos\ClienteposRequest;
use App\Http\Requests\Pos\ClienteposUpdateRequest;
use App\Http\Resources\Pos\ClienteposResource;
use App\Interfaces\Pos\ClienteposService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ClienteposController extends Controller
{
    public function __construct(private readonly ClienteposService $service) {}

    public function index(ClienteposListRequest $request): JsonResponse
    {
        try {
            return $this->sendResponse(ClienteposResource::collection($this->service->paginate((string) ($request->validated()['search'] ?? ''))), 'ok', 200, true);
        } catch (Throwable $exception) {
            $this->logError('ClienteposController index', $exception);

            return $this->sendError('Error al listar los clientes POS.', null, 500);
        }
    }

    public function store(ClienteposRequest $request): JsonResponse
    {
        try {
            if (! $this->service->create(ClienteposDTO::fromRequest($request->validated())->toArray())) {
                return $this->sendError('No se pudo crear el cliente POS.', false, 422);
            }

            return $this->sendResponse(true, 'Cliente POS creado correctamente.', 201);
        } catch (Throwable $exception) {
            $this->logError('ClienteposController store', $exception);

            return $this->sendError('Error al crear el cliente POS.', null, 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $client = $this->service->findById($id);

            return $client === null
                ? $this->sendError('Cliente POS no encontrado.', null, 404)
                : $this->sendResponse(ClienteposResource::make($client), 'ok');
        } catch (Throwable $exception) {
            $this->logError('ClienteposController show', $exception);

            return $this->sendError('Error al buscar el cliente POS.', null, 500);
        }
    }

    public function update(ClienteposUpdateRequest $request): JsonResponse
    {
        try {
            if (! $this->service->update($request->integer('id'), ClienteposDTO::fromRequest($request->validated())->toArray())) {
                return $this->sendError('Cliente POS no encontrado.', false, 404);
            }

            return $this->sendResponse(true, 'Cliente POS actualizado correctamente.');
        } catch (Throwable $exception) {
            $this->logError('ClienteposController update', $exception);

            return $this->sendError('Error al actualizar el cliente POS.', null, 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            if (! $this->service->delete($id)) {
                return $this->sendError('Cliente POS no encontrado.', false, 404);
            }

            return $this->sendResponse(true, 'Cliente POS eliminado correctamente.');
        } catch (Throwable $exception) {
            $this->logError('ClienteposController destroy', $exception);

            return $this->sendError('No se pudo eliminar el cliente POS.', null, 422);
        }
    }
}
