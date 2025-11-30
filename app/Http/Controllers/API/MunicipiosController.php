<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\MunicipiosResource;
use App\Models\Municipios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MunicipiosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = Municipios::all();
        if ($list->count() > 0) {
            return $this->sendResponse(MunicipiosResource::collection($list), 'success');
        }
        return $this->sendResponse(null, 'No se encontraron informacion', 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'departamento' => 'required|integer|exists:departamentos,id',
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 400);
        }

        $municipio = Municipios::create($request->all());
        if ($municipio) {
            return $this->sendResponse(null, 'Municipio creado');
        }
        return $this->sendResponse(null, 'No se pudo crear el municipio', 500);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $municipio = Municipios::find($id);
        if ($municipio) {
            return $this->sendResponse( MunicipiosResource::make($municipio), 'success');
        }
        return $this->sendResponse(null, 'No se encontró el municipio', 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'departamento' => 'required|integer|exists:departamentos,id',
        ]);
        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 400);
        }
        $municipio = Municipios::find($id);
        if (!$municipio) {
            return $this->sendResponse(null, 'No se encontró el municipio', 404);
        }
        $municipio->update($request->all());
        if ($municipio) {
            return $this->sendResponse(null, 'Municipio actualizado');
        }
        return $this->sendResponse(null, 'No se pudo actualizar el municipio', 500);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $municipio = Municipios::find($id);
        if (!$municipio) {
            return $this->sendResponse(null, 'No se encontró el municipio', 404);
        }
        if ($municipio->delete()) {
            return $this->sendResponse(null, 'Municipio eliminado');
        }
        return $this->sendResponse(null, 'No se pudo eliminar el municipio', 500);
    }

    // find by departamento 
    public function findbydepartamento($id)
    {
        $municipios = Municipios::where('departamento', $id)->get();
        if ($municipios->count() > 0) {
            return $this->sendResponse(MunicipiosResource::collection($municipios), 'success');
        }
        return $this->sendResponse(null, 'No se encontraron municipios', 404);
    }
}
