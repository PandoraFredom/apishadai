<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartamentoResource;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = Departamento::all();
        if ($list->count() > 0) {
            return $this->sendResponse(DepartamentoResource::collection($list), 'success');
        }
        return $this->sendResponse(null, 'No se encontraron informacion', 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:departamentos,nombre',
        ]);
        if ($validate->fails()) {
            return $this->sendResponse(false, $validate->errors()->first(), 422);
        }
        $departamento = Departamento::create([
            'nombre' => $request->nombre,
        ]);
        if ($departamento) {
            return $this->sendResponse(true, 'Departamento creado correctamente', 200);
        }
        return $this->sendResponse(true, 'Error al crear el departamento', 500);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $departamento = Departamento::find($id);
        if ($departamento) {
            return $this->sendResponse( DepartamentoResource::make($departamento), 'success');
        }
        return $this->sendResponse(null, 'No se encontraron informacion', 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validate = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:departamentos,nombre,' . $id,
        ]);
        if ($validate->fails()) {
            return $this->sendResponse(false, $validate->errors()->first(), 422);
        }
        $departamento = Departamento::find($id);
        if ($departamento) {
            $departamento->update([
                'nombre' => $request->nombre,
            ]);
            return $this->sendResponse(true, 'Departamento actualizado correctamente', 200);
        }
        return $this->sendResponse(false, 'No se encontraron informacion', 404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $departamento = Departamento::find($id);
        if ($departamento) {

           try {
                $departamento->delete();
                return $this->sendResponse(true, 'Departamento eliminado correctamente', 200);
            } catch (\Exception $e) {
                return $this->sendResponse(false, 'Departamento no disponible para eliminar', 500);
            }
        
        }
        return $this->sendResponse(false, 'No se encontraron informacion', 404);
    }
}
