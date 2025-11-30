<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ModulosResource;
use App\Models\Modulos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ModulosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = Modulos::all();
        if ($list->count() > 0) {
            return $this->sendResponse(ModulosResource::collection($list), 'success');
        }
        return $this->sendResponse(null, 'No se encontraron informacion', 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'nombre' => 'required|string|unique:modulos,nombre',
            'codigo' => 'required|string|unique:modulos,codigo',
            'estado.id' => 'required|integer|exists:modulo_estados,id',
        ]);

        if ($validate->fails()) {
            return $this->sendResponse(false, $validate->errors()->first(), 400);
        }

        try {
            $input = $request->all();
            $input['codigo'] = strtoupper($input['codigo']);
            $input['estado'] = $input['estado']['id'];
            $modulo = Modulos::create($input);
            if ($modulo) {
                return $this->sendResponse(true, 'Modulo registrado');
            }
            return $this->sendResponse(false, 'Ocurrio un error al registrar el modulo', 500);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'Ocurrio un error al registrar el modulo', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $obj = Modulos::find($id);
        if ($obj) {
            return $this->sendResponse(ModulosResource::make($obj), "success");
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'estado.id' => "required|integer|exists:modulo_estados,id",
        ]);

        if ($validate->fails()) {
            return $this->sendResponse(false, $validate->errors()->first(), 400);
        }

        try {
            $input = $request->all();
            $input['estado'] = $input['estado']['id'];
            $id = $input['id'];
            $update = Modulos::find($id)->update($input);
            if ($update) {
                return $this->sendResponse(true, 'Modulo actualizado');
            }
            return $this->sendResponse(false, 'Ocurrio un error al actualizar el modulo', 500);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'Ocurrio un error al actualizar el modulo', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $obj = Modulos::find($id);
            if ($obj) {
                $obj->delete();
                return $this->sendResponse(true, 'Modulo eliminado');
            }
            return $this->sendResponse(false, 'No se encontro informacion', 404);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'Modulo no disponible para eliminar', 500);
        }
    }
}
