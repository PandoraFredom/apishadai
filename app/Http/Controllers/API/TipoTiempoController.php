<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\TipoTiempoResource;
use App\Models\TipoTiempo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TipoTiempoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list  = TipoTiempo::all();
        if ($list->isEmpty()) {
            return $this->sendResponse(null, 'No hay tipos de tiempo disponibles', 404);
        }
        return $this->sendResponse(TipoTiempoResource::collection($list), 'data', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'cantidad' => 'required|string|max:50',
            'unidad' => 'required|string|max:50',
        ]);
        if ($validate->fails()) {
            return $this->sendResponse(false, 'Error de validación: ' . $validate->errors()->first(), 400);
        }
        $data = $request->only(['nombre', 'cantidad', 'unidad']);
        $tipoTiempo = TipoTiempo::create($data);
        if (!$tipoTiempo) {
            return $this->sendResponse(false, 'Error al crear el tipo de tiempo', 500);
        }
        return $this->sendResponse(true, 'Tipo de tiempo creado con éxito', 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tipoTiempo = TipoTiempo::find($id);
        if (!$tipoTiempo) {
            return $this->sendResponse(null, 'Tipo de tiempo no encontrado', 404);
        }
        return $this->sendResponse( TipoTiempoResource::make($tipoTiempo), 'data', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tipoTiempo = TipoTiempo::find($id);
        if (!$tipoTiempo) {
            return $this->sendResponse(null, 'Tipo de tiempo no encontrado', 404);
        }

        $validate = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'cantidad' => 'required|string|max:50',
            'unidad' => 'required|string|max:50',
        ]);
        if ($validate->fails()) {
            return $this->sendResponse(null, 'Error de validación: ' . $validate->errors()->first(), 400);
        }

        $data = $request->only(['nombre', 'cantidad', 'unidad']);
        $tipoTiempo->update($data);
        if (!$tipoTiempo) {
            return $this->sendResponse(false, 'Error al actualizar el tipo de tiempo', 500);
        }
        return $this->sendResponse(true, 'Tipo de tiempo actualizado con éxito', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tipoTiempo = TipoTiempo::find($id);
        if (!$tipoTiempo) {
            return $this->sendResponse(null, 'Tipo de tiempo no encontrado', 404);
        }

       try {
            $tipoTiempo->delete();
            return $this->sendResponse(true, 'Tipo de tiempo eliminado con éxito', 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'Tiempo no Disponible' , 500);
        }

        
    }
}
