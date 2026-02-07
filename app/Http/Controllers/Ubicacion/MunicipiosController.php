<?php

namespace App\Http\Controllers\Ubicacion;

use App\DTOs\MunicipioDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ubicacion\MunicipioRequest;
use App\Http\Resources\MunicipiosResource;
use App\Http\Resources\Ubicacion\MunicipiosResourceSingle;
use App\Interfaces\Ubicacion\MunicipiosService;


class MunicipiosController extends Controller
{

    public function __construct(private MunicipiosService $service) {}


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = $this->service->getAll();
        if (!$list) {
            return $this->sendResponse(null, 'No se encontraron municipios', 404);
        }
        return $this->sendResponse(MunicipiosResourceSingle::collection($list), 'ok', 200, false);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MunicipioRequest $request)
    {
        $dto = MunicipioDTO::fromRequest($request->validated());
        $save = $this->service->create($dto->toArray());

        if (!$save) {
            return $this->sendResponse(false, 'Error al crear el municipio', 500);
        }
        return $this->sendResponse(true, 'Municipio creado con éxito', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $municipio = $this->service->findById($id);
        if (!$municipio) {
            return $this->sendResponse(null, 'Municipio no encontrado', 404);
        }
        return $this->sendResponse( MunicipiosResource::make($municipio), 'ok', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MunicipioRequest $request)
    {
        $dto = MunicipioDTO::fromRequest($request->validated());
        $save = $this->service->update($dto->id, $dto->toArray());

        if (!$save) {
            return $this->sendResponse(false, 'Error al actualizar el municipio', 500);
        }
        return $this->sendResponse(true, 'Municipio actualizado con éxito', 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $delete = $this->service->delete($id);
        if (!$delete) {
            return $this->sendResponse(false, 'Error al eliminar el municipio', 500);
        }
        return $this->sendResponse(true, 'Municipio eliminado con éxito', 200);
    }


    public function getByDepartamento(int $did)
    {
        $list = $this->service->getByDepartamento($did);
        if (!$list) {
            return $this->sendResponse(false, 'No se encontraron municipios para el departamento especificado', 404);
        }
        return $this->sendResponse(MunicipiosResourceSingle::collection($list), 'ok', 200, false);
    }
}
