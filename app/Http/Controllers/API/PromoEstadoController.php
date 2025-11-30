<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PromoEstadoResource;
use App\Models\PromoEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PromoEstadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = PromoEstado::all();

        if ($list->isEmpty()) {
            return $this->sendResponse(null, 'No hay datos para mostrar', 404);
        }
        return $this->sendResponse(PromoEstadoResource::collection($list), 'Lista de estados de promociones', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:255',
        ]);
        if ($validate->fails()) {
            return $this->sendResponse(false, $validate->errors()->first(), 422);
        }
        $input = $request->all();
        $promoEstado = PromoEstado::create($input);
        if ($promoEstado) {
            return $this->sendResponse(true, 'Estado de promocion creado');
        }
        return $this->sendResponse(false, 'No se pudo crear la informacion', 500);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $promoEstado = PromoEstado::find($id);
        if ($promoEstado) {
            return $this->sendResponse(PromoEstadoResource::make($promoEstado), "success");
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validate = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:255',
        ]);
        if ($validate->fails()) {
            return $this->sendResponse(false, $validate->errors()->first(), 422);
        }
        $promoEstado = PromoEstado::find($id);
        if ($promoEstado) {
            $promoEstado->update($request->all());
            return $this->sendResponse(true, 'Estado de promocion actualizado');
        }
        return $this->sendResponse(false, 'No se encontro informacion', 404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $promoEstado = PromoEstado::find($id);
        if ($promoEstado) {
            if ($promoEstado->delete()) {
                return $this->sendResponse(true, 'Estado de promocion eliminado');
            }
            return $this->sendResponse(false, 'No se pudo eliminar la informacion', 500);
        }
        return $this->sendResponse(false, 'No se encontro informacion', 404);
    }
}
