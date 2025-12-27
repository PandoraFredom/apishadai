<?php

namespace App\Http\Controllers\API;

use App\DTOs\PermisoDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permiso\PermisoRequest;
use App\Http\Resources\ActionsVistasResource;
use App\Http\Resources\Modulos\ModulosResourceCbx;
use App\Http\Resources\PermisosResource;
use App\Http\Resources\TipoTiempoResource;
use App\Http\Resources\Vistas\VistasResourceCbx;
use App\Interfaces\Config\PermisoService;
use App\Interfaces\Config\TipoTiempoService;
use App\Utils\LifetimeResolver;
use Illuminate\Http\Request;

class PermisosController extends Controller
{

    public function __construct(
        private PermisoService $service,
        private TipoTiempoService $tipoTiempoService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PermisoRequest $request)
    {
        try {
            $dto = PermisoDTO::fromRequest($request->validated());

            $existPermiso = $this->service->exists([
                'usuario' => $dto->usuario,
                'modulo' => $dto->modulo,
                'vista' => $dto->vista,
                'actionvista' => $dto->actionvista,
            ]);

            if ($existPermiso) {
                return $this->sendResponse(false, 'El permiso ya existe', 409);
            }

            $tipoT = $this->tipoTiempoService->findOrFail($dto->tipo_tiempo);
            if (!$tipoT) {
                return $this->sendResponse(false, 'Tipo de tiempo no encontrado', 404);
            }

            $lifetime = LifetimeResolver::resolve($tipoT);

            $created = $this->service->create([
                'usuario' => $dto->usuario,
                'modulo' => $dto->modulo,
                'vista' => $dto->vista,
                'actionvista' => $dto->actionvista,
                'tipo_tiempo' => $dto->tipo_tiempo,
                'lifetime' => $lifetime,
            ]);
            if ($created) {
                return $this->sendResponse(true, 'Permiso creado exitosamente', 201);
            }
            return $this->sendResponse(false, 'Error al crear el permiso', 500);
        } catch (\Throwable $th) {
            $this->logError('PermisosController store', $th);
            return $this->sendResponse(false, 'Error al crear el permiso: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->sendResponse(false, 'Not implemented', 501);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return $this->sendResponse(false, 'Not implemented', 501);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $permiso = $this->service->findOrFail($id);
            if (!$permiso) {
                return $this->sendResponse(false, 'Permiso no encontrado', 404);
            }

            $deleted = $this->service->delete($id);
            if ($deleted) {
                return $this->sendResponse(true, 'Permiso eliminado exitosamente', 200);
            }
            return $this->sendResponse(false, 'Error al eliminar el permiso', 500);
        } catch (\Throwable $th) {
            $this->logError('PermisosController destroy', $th);
            return $this->sendResponse(false, 'Error al eliminar el permiso: ' . $th->getMessage(), 500);
        }
    }

    public function findbyuser($id)
    {
        $permisos = $this->service->listByUserId($id);

        if ($permisos->isEmpty()) {
            return $this->sendResponse([], 'No se Encontraron Datos');
        }
        return $this->sendResponse(PermisosResource::collection($permisos), 'ok');
    }

    public function get_moduloList()
    {
        $list = $this->service->get_ModuloListCbx();
        if (!$list) {
            return $this->sendResponse(null, 'No se Encontraron Datos', 404);
        }
        return $this->sendResponse(ModulosResourceCbx::collection($list), 'ok');
    }
    public function get_vistasByModulo(int $moduloId)
    {
        $list = $this->service->get_VistasByModulo($moduloId);
        if (!$list) {
            return $this->sendResponse(null, 'No se Encontraron Datos', 404);
        }
        return $this->sendResponse(VistasResourceCbx::collection($list), 'ok');
    }
    public function get_accionesByVista(int $vistaId)
    {
        $list = $this->service->get_AccionesByVista($vistaId);
        if (!$list) {
            return $this->sendResponse(null, 'No se Encontraron Datos', 404);
        }
        return $this->sendResponse(ActionsVistasResource::collection($list), 'ok');
    }
    public function get_tipostiempoList()
    {
        $list = $this->service->tiposTiempoList();
        if (!$list) {
            return $this->sendResponse(null, 'No se Encontraron Datos', 404);
        }
        return $this->sendResponse(TipoTiempoResource::collection($list), 'ok');
    }
}
