<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\VistasResource;
use App\Models\Vistas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VistasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = Vistas::all();
        if ($list->count() > 0) {
            return $this->sendResponse(VistasResource::collection($list), 'success');
        }
        return $this->sendResponse(null, 'No se encontraron informacion', 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'modulo.id' => 'required|integer|exists:modulos,id',
            'nombre' => 'required|string',
            'codigo' => 'required|string|unique:vistas,codigo',
            'estado.id' => 'required|integer|exists:vista_estados,id',
        ]);

        if ($validate->fails()) {
            return $this->sendResponse(false, $validate->errors()->first(), 422);
        }
        // Check if a vista with the same name and module id already exists
        if ($this->exist_samenameWhithModuleId($request->input('nombre'), $request->input('modulo.id'))) {
            return $this->sendResponse(false, 'Ya existe una vista con el mismo nombre y modulo', 422);
        }


        try {
            $input = $request->all();
            $input['codigo'] = strtoupper($input['codigo']);
            $input['modulo'] = $input['modulo']['id'];
            $input['estado'] = $input['estado']['id'];
            $vista = Vistas::create($input);
            if ($vista) {
                return $this->sendResponse(true, 'Vista creada');
            }
            return $this->sendResponse(false, 'No se pudo crear la informacion', 500);
        } catch (\Throwable $e) {
            return $this->sendResponse(false, 'No se pudo crear la informacion', 500);
        }
    }

    private function exist_samenameWhithModuleId($name, $module)
    {

        $obj = Vistas::where('modulo', $module)->where('nombre', $name)->first();
        return $obj ? true : false;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $obj = Vistas::find($id);
        if ($obj) {
            return $this->sendResponse(VistasResource::make($obj), "success");
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'estado.id' => "required|integer|exists:vista_estados,id",
        ]);

        if ($validate->fails()) {
            return $this->sendResponse(false, $validate->errors()->first(), 422);
        }
        try {

            $data = [
                'estado' => $request->input('estado.id'),
            ];
            $id = $request->input('id');
            $vista = Vistas::find($id);

            if ($vista) {
                $vista->update($data);
                return $this->sendResponse(true, 'Vista actualizada');
            }
            return $this->sendResponse(false, 'No se econtro informacion', 500);
        } catch (\Throwable $e) {
            return $this->sendResponse(false, 'No se pudo actualizar la informacion : ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $obj = Vistas::find($id);
        if ($obj) {

            try {
                $obj->delete();
                return $this->sendResponse(true, 'Vista eliminada');
            } catch (\Throwable $e) {
                return $this->sendResponse(false, 'Vista no disponible para eliminar', 500);
            }
        } else {
            return $this->sendResponse(false, 'No se encontro informacion', 404);
        }
    }

    public function findbyModule($id)
    {
        $list = Vistas::where('modulo', $id)->get();
        if ($list->count() > 0) {
            return $this->sendResponse(VistasResource::collection($list), "success");
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }
}
