<?php

namespace App\Http\Controllers\API;

use App\DTOs\PromosDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Promos\PromosRequest;
use App\Http\Requests\Promos\PromosUpdateRequest;
use App\Http\Requests\Util\FilterRequest;
use App\Http\Resources\PromocionesResource;
use App\Http\Resources\PromoEstadoResource;
use App\Interfaces\Promos\PromocionesService;


class PromocionesController extends Controller
{

    public function __construct(private PromocionesService $service) {}


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = $this->service->paginate();
        if ($list->isEmpty()) {
            return $this->sendResponse(null, 'No hay sorteos disponibles.', 404);
        }
        return $this->sendResponse(PromocionesResource::collection($list), 'ok', 200, true);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PromosRequest $request)
    {

        try {
            $dto = PromosDTO::fromRequest($request->validated());
            $data = [
                'nombre' => $dto->nombre,
                'descripcion' => $dto->descripcion,
                'fecha_inicio' =>  date('Y-m-d', strtotime($dto->fecha_inicio)),
                'fecha_fin' => date('Y-m-d', strtotime($dto->fecha_fin)),
                'estado' => $dto->estado,
            ];

            $create = $this->service->create($data);
            if (!$create) {
                return $this->sendResponse(false, 'Error al crear Sorteo.', 500);
            }
            return $this->sendResponse(true, 'Sorteo creado exitosamente.', 201);
        } catch (\Throwable $th) {
            $this->logError('PromocionesController store', $th);
            return $this->sendResponse(false, 'Error inesperado al crear Sorteo.', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $promo = $this->service->findById($id);
        if (!$promo) {
            return $this->sendResponse(null, 'Sorteo no encontrado.', 404);
        }
        return $this->sendResponse(PromocionesResource::make($promo), 'ok');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PromosUpdateRequest $request)
    {
        try {
            $dto = PromosDTO::fromUpdateRequest($request->validated());
            $anotherActive = $this->service->another_active_promo($dto->id);
            if ($anotherActive) {
                return $this->sendResponse(false, 'Ya existe otro sorteo activo. Solo puede haber un sorteo activo a la vez.', 400);
            }

            $update = $this->service->update($dto->id, $dto->toUpdateArray());
            if (!$update) {
                return $this->sendResponse(false, 'Error al actualizar el sorteo.', 500);
            }
            return $this->sendResponse(true, 'Sorteo actualizado exitosamente.', 200);
        } catch (\Throwable $th) {
            $this->logError('PromocionesController update', $th);
            return $this->sendResponse(false, 'Error inesperado al actualizar el sorteo.', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $delete = $this->service->delete($id);
            if (!$delete) {
                return $this->sendResponse(false, 'Error al eliminar el sorteo.', 500);
            }
            return $this->sendResponse(true, 'Sorteo eliminado exitosamente.', 200);
        } catch (\Throwable $th) {
            $this->logError('PromocionesController destroy', $th);
            return $this->sendResponse(false, 'Sorteo no disponible para eliminar.', 500);
        }
    }

    public function filter(FilterRequest $request) {}


    public function get_estadosList()
    {
        $estados = $this->service->get_estadosList();
        return $this->sendResponse(PromoEstadoResource::collection($estados), 'ok');
    }
}
