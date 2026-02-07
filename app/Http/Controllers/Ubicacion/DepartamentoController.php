<?php

namespace App\Http\Controllers\Ubicacion;

use App\DTOs\DepartamentoDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ubicacion\DepartamentoRequest;
use App\Http\Resources\DepartamentoResource;
use App\Interfaces\Ubicacion\DepartamentosService;


class DepartamentoController extends Controller
{

    public function __construct(private DepartamentosService $service) {}


    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $list = $this->service->getAll();
        if (!$list) {
            return $this->sendResponse(null, 'No se encontraron departamentos', 404);
        }
        return $this->sendResponse(DepartamentoResource::collection($list), 'ok', 200, false);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DepartamentoRequest $request)
    {
        $dto =  DepartamentoDTO::fromRequest($request->validated());

        $save = $this->service->create($dto->toArray());
        if (!$save) {
            return $this->sendResponse(false, 'Error al crear el departamento', 500);
        }

        return $this->sendResponse(true, 'Departamento creado con exito', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $departamento = $this->service->findById($id);
        if (!$departamento) {
            return $this->sendResponse(null, 'Departamento no encontrado', 404);
        }
        return $this->sendResponse(DepartamentoResource::make($departamento), message: 'ok');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DepartamentoRequest $request)
    {
        $dto =  DepartamentoDTO::fromRequest($request->validated());

        $save = $this->service->update($dto->id, $dto->toArray());
        if (!$save) {
            return $this->sendResponse(false, 'Error al actualizar el departamento', 500);
        }

        return $this->sendResponse(true, 'Departamento actualizado con exito', 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $departamento = $this->service->findById($id);
        if (!$departamento) {
            return $this->sendResponse(false, 'Departamento no encontrado', 404);
        }
        return $this->sendResponse(true, 'ok', 200);
    }
}
