<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClienteUbicacionResource;
use App\Models\ClienteUbicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClienteUbicacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = ClienteUbicacion::all();
        if ($list->isEmpty()) {
            return $this->sendResponse(null, 'No hay ubicaciones registradas', 404);
        }
        return $this->sendResponse(ClienteUbicacionResource::collection($list), 'Lista de ubicaciones', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //user validation facades
        $validate = Validator::make($request->all(), [
           'departamento.id' => 'required|integer|exists:departamentos,id',
           'municipio.id' => 'required|integer|exists:municipios,id',
        ]);
        if ($validate->fails()) {
            return $this->sendResponse(null, $validate->errors()->first(), 422);
        }

        $ubicacion = new ClienteUbicacion();
        $ubicacion->departamento = $request->departamento['id'];
        $ubicacion->municipio = $request->municipio['id'];
        $ubicacion->save();
        if ($ubicacion) {
            return $this->sendResponse(null, 'Ubicacion registrada', 201);
        }
        return $this->sendResponse(null, 'Error al registrar la ubicacion', 500);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ubicacion = ClienteUbicacion::find($id);
        if (!$ubicacion) {
            return $this->sendResponse(null, 'Ubicacion no encontrada', 404);
        }
        return $this->sendResponse(null, 'Ubicacion encontrada', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //user validation facades
        $validate = Validator::make($request->all(), [
            'departamento.id' => 'required|integer|exists:departamentos,id',
            'municipio.id' => 'required|integer|exists:municipios,id',
        ]);
        if ($validate->fails()) {
            return $this->sendResponse(null, $validate->errors()->first(), 422);
        }
        $ubicacion = ClienteUbicacion::find($id);
        if (!$ubicacion) {
            return $this->sendResponse(null, 'Ubicacion no encontrada', 404);
        }
        $ubicacion->departamento = $request->departamento['id'];
        $ubicacion->municipio = $request->municipio['id'];
        $ubicacion->save();
        if ($ubicacion) {
            return $this->sendResponse(null, 'Ubicacion actualizada', 200);
        }
        return $this->sendResponse(null, 'Error al actualizar la ubicacion', 500);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ubicacion = ClienteUbicacion::find($id);
        if (!$ubicacion) {
            return $this->sendResponse(null, 'Ubicacion no encontrada', 404);
        }
        $ubicacion->delete();
        if ($ubicacion) {
            return $this->sendResponse(null, 'Ubicacion eliminada', 200);
        }
        return $this->sendResponse(null, 'Error al eliminar la ubicacion', 500);
    }
}
