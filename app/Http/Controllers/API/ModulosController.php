<?php

namespace App\Http\Controllers\API;

use App\DTOs\ModulosDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ModuloRequest;
use App\Http\Requests\ModuloUpdateRequest;
use App\Http\Resources\ModulosResource;
use App\Interfaces\Config\ModulosRepositoryInterface;

class ModulosController extends Controller
{
  

    public function __construct(private ModulosRepositoryInterface $service)
    {

    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {


        $data = $this->service->paginate();
        if ($data) {
            return $this->sendResponse(
                ModulosResource::collection($data),
                'success',
                200,
                true
            );
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ModuloRequest $request)
    {
        try {
            $dto = ModulosDTO::fromRequest($request->all());
            $data = $this->service->create($dto->toArray());
            if ($data) {
                return $this->sendResponse(true, 'Modulo creado con exito');
            }
            return $this->sendResponse(false, 'Error al crear el modulo', 500);
        } catch (\Throwable $th) {
            return $this->sendResponse(false, 'Error al crear el modulo: ' . $th->getMessage(), 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = $this->service->findById($id);
        if ($data) {
            return $this->sendResponse(
                ModulosResource::make($data),
                'success',
                200

            );
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ModuloUpdateRequest $request)
    {
        try {
            $dto = ModulosDTO::fromUpdateRequest($request->all());
            $data = $this->service->update($dto->id, $dto->toUpdateArray());
            if ($data) {
                return $this->sendResponse(true, 'Modulo actualizado con exito');
            }
            return $this->sendResponse(false, 'Error al actualizar el modulo', 500);
        } catch (\Throwable $th) {
            return $this->sendResponse(false, 'Error al actualizar el modulo: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $data = $this->service->delete($id);
            if ($data) {
                return $this->sendResponse(true, 'Modulo eliminado con exito');
            }
            return $this->sendResponse(false, 'Error al eliminar el modulo', 500);
        } catch (\Throwable $th) {
            return $this->sendResponse(false, 'Error al eliminar el modulo: ' . $th->getMessage(), 500);
        }
    }
}
