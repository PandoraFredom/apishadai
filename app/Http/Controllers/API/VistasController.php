<?php

namespace App\Http\Controllers\API;

use App\DTOs\AccionesVistaDTO;
use App\DTOs\VistaDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\AccionesVistaRequest;
use App\Http\Requests\Vista\VistaRequest;
use App\Http\Requests\Vista\VistaUpdateRequest;
use App\Http\Resources\ActionsVistasResource;
use App\Http\Resources\Modulos\ModulosResourceCbx;
use App\Http\Resources\VistaEstadosResource;
use App\Http\Resources\Vistas\VistasResource;
use App\Interfaces\Config\VistaRepositoryInterface;

class VistasController extends Controller
{

    public function __construct(private VistaRepositoryInterface $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = $this->service->paginate();
        if (!$list) {
            return $this->sendResponse(null, 'No se encontraron informacion', 404);
        }
        return $this->sendResponse(
            VistasResource::collection($list),
            'success',
            200,
            true
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VistaRequest $request)
    {
        $dto = VistaDTO::fromRequest($request->Validated());

        if ($this->service->exist_samenameWhithModuleId($dto->nombre, $dto->modulo) != null) {
            return $this->sendResponse(false, 'Ya existe una vista con el mismo nombre y modulo', 422);
        }

        try {
            $vista = $this->service->create($dto->toArray());
            if ($vista) {
                return $this->sendResponse(true, 'Vista creada');
            }
            return $this->sendResponse(false, 'No se pudo crear la informacion', 500);
        } catch (\Throwable $e) {
            $this->logError('Error al crear la vista: ', $e);
            return $this->sendResponse(false, 'No se pudo crear la informacion', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $obj = $this->service->findById($id);
        if (!$obj) {
            return $this->sendResponse(null, 'No se encontro informacion', 404);
        }
        return $this->sendResponse(VistasResource::make($obj), "success");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VistaUpdateRequest $request)
    {
        try {
            $dto = VistaDTO::fromUpdateRequest($request->Validated());
            $update = $this->service->update($dto->id, $dto->toUpdateArray());
            if ($update) {
                return $this->sendResponse(true, 'Vista actualizada');
            }
            return $this->sendResponse(false, 'No se econtro informacion', 500);
        } catch (\Throwable $th) {
            $this->logError('Error al actualizar la vista: ', $th);
            return $this->sendResponse(false, 'No se pudo actualizar la informacion', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $delete = $this->service->delete($id);
        if (!$delete) {
            return $this->sendResponse(false, 'No se pudo eliminar la informacion', 500);
        }
        return $this->sendResponse(true, 'Vista eliminada');
    }

    public function findbyModule(string $id)
    {
        $list = $this->service->findByModule($id);
        if (!$list) {
            return $this->sendResponse(null, 'No se encontraron informacion', 404);
        }
        return $this->sendResponse(
            VistasResource::collection($list),
            'success',
            200,
            true
        );
    }

    public function estadosList()
    {
        $list = $this->service->estadosList();
        if (!$list) {
            return $this->sendResponse(null, 'No se encontraron informacion', 404);
        }
        return $this->sendResponse(
            VistaEstadosResource::collection($list),
            'success',
            200,
            false
        );
    }
    public function modulosList()
    {
        $list = $this->service->modulosList();
        if (!$list) {
            return $this->sendResponse(null, 'No se encontraron informacion', 404);
        }
        return $this->sendResponse(
            ModulosResourceCbx::collection($list),
            'success',
            200,
            false
        );
    }


    public function acctionList($vistaId)
    {
        $list = $this->service->acctionList($vistaId);
        if (!$list) {
            return $this->sendResponse(null, 'No se encontraron informacion', 404);
        }
        return $this->sendResponse(
            ActionsVistasResource::collection($list),
            'success',
            200,
            false
        );
    }
    public function deleteAccion($id)
    {
        $delete = $this->service->deleteAccion($id);
        if (!$delete) {
            return $this->sendResponse(false, 'No se pudo eliminar la informacion', 500);
        }
        return $this->sendResponse(true, 'Accion eliminada');
    }
    public function createAccion(AccionesVistaRequest $request)
    {
        try {
            $dto = AccionesVistaDTO::fromRequest($request->Validated());
            $create = $this->service->createAccion($dto->toArray());
            if (!$create) {
                return $this->sendResponse(false, 'No se pudo crear la informacion', 500);
            }
            return $this->sendResponse(true, 'Accion creada');
        } catch (\Throwable $th) {
            $this->logError('Error al crear la accion de la vista: ', $th);
            return $this->sendResponse(false, 'No se pudo crear la informacion', 500);
        }
    }
}
