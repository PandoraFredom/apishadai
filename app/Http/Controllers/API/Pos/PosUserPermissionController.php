<?php

namespace App\Http\Controllers\API\Pos;

use App\DTO\PermisoDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\PosUserPermissionRequest;
use App\Models\Pos\BotonPos;
use App\Models\Pos\ModuloPos;
use App\Models\Pos\VistaPos;
use App\Models\TipoTiempo;
use App\Repositories\Pos\PosUserPermissionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PosUserPermissionController extends Controller
{
    public function __construct(private readonly PosUserPermissionRepository $repository) {}

    public function index(int $id): JsonResponse
    {
        return $this->sendResponse($this->repository->list($id), 'ok');
    }

    public function grouped(int $id): JsonResponse
    {
        return $this->sendResponse($this->repository->grouped($id), 'ok');
    }

    public function modules(): JsonResponse
    {
        return $this->sendResponse(ModuloPos::where('activo', true)->orderBy('id')->get(['id', 'nombre', 'codigo']), 'ok');
    }

    public function views(int $id): JsonResponse
    {
        return $this->sendResponse(VistaPos::where('modulo_id', $id)->where('activo', true)->orderBy('id')->get(['id', 'nombre', 'codigo']), 'ok');
    }

    public function actions(int $id): JsonResponse
    {
        return $this->sendResponse(BotonPos::where('vista_id', $id)->where('activo', true)->orderBy('id')->get(['id', 'nombre', 'codigo']), 'ok');
    }

    public function times(): JsonResponse
    {
        return $this->sendResponse(TipoTiempo::orderBy('id')->get(['id', 'nombre']), 'ok');
    }

    public function store(PosUserPermissionRequest $request): JsonResponse
    {
        try {
            return $this->repository->create(PermisoDTO::fromRequest($request->validated())) ? $this->sendResponse(true, 'Permiso POS creado.', 201) : $this->sendError('El permiso POS ya existe.', ['errors' => ['actionvista' => ['El usuario ya tiene esta acción. Elimina la asignación anterior para cambiar su duración.']]], 409);
        } catch (ValidationException $e) {
            return $this->sendError($e->getMessage(), ['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::warning('No se pudo asignar el permiso POS.', ['exception' => $e::class]);

            return $this->sendError('No se pudo asignar el permiso POS.', null, 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->repository->delete($id) ? $this->sendResponse(true, 'Permiso POS eliminado.') : $this->sendError('Permiso POS no encontrado.',null,404);
    }
}
