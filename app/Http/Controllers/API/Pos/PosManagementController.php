<?php

namespace App\Http\Controllers\API\Pos;

use App\DTO\PosManagementDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\PosManagementRequest;
use App\Http\Resources\Pos\PosManagementResource;
use App\Repositories\Pos\PosManagementRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PosManagementController extends Controller
{
    public function __construct(private readonly PosManagementRepository $repository) {}

    public function index(Request $request): JsonResponse
    {
        return $this->sendResponse(PosManagementResource::collection($this->repository->paginate($request->route('resource'))), 'ok', 200, true);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $model = $this->repository->find($request->route('resource'), $id);

        return $model === null ? $this->sendError('Registro POS no encontrado.', null, 404) : $this->sendResponse(PosManagementResource::make($model), 'ok');
    }

    public function store(PosManagementRequest $request): JsonResponse
    {
        return $this->persist($request, null);
    }

    public function update(PosManagementRequest $request, int $id): JsonResponse
    {
        return $this->persist($request, $id);
    }

    private function persist(PosManagementRequest $request, ?int $id): JsonResponse
    {
        try {
            $model = $this->repository->save($request->route('resource'), $id, PosManagementDTO::fromRequest($request->validated()));

            return $model === null ? $this->sendError('Registro POS no encontrado.', null, 404)
                : $this->sendResponse(PosManagementResource::make($model), 'Registro POS guardado.', $id === null ? 201 : 200);
        } catch (ValidationException $exception) {
            return $this->sendError($exception->getMessage(), ['errors' => $exception->errors()], 422);
        } catch (\Throwable $exception) {
            Log::warning('No se pudo guardar el recurso POS.', ['exception' => $exception::class]);

            return $this->sendError('No se pudo guardar el registro POS.', null, 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        return $this->repository->deactivate($request->route('resource'), $id)
            ? $this->sendResponse(true, 'Registro POS desactivado.') : $this->sendError('Registro POS no encontrado.', null, 404);
    }
}
