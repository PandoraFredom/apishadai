<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ModuloEstadoResoure;
use App\Models\ModuloEstados;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ModuloEstadoController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Get all the modulo estados
        $list = ModuloEstados::all();

        if ($list->isNotEmpty()) {
            return $this->sendResponse(ModuloEstadoResoure::collection($list), 'success');
        }
        // If there are no modulo estados, return a 404 error
        return $this->sendResponse(null, 'No se encontraron modulos', 404);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|string|unique:modulo_estados,descripcion',
        ]);

        // If the validation fails, return the error message
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            if ($errorMessage === null) {
                $errorMessage = 'Error al crear';
            }
            return $this->sendResponse(false, $errorMessage);
        }

        // Create the new modulo estado
        try {
            $data = $request->all();
            $moduloEstado = ModuloEstados::create($data);
        } catch (\Exception $e) {
            return $this->sendResponse(null, 'Error al crear', 500);
        }

        // Return appropriate response based on creation success
        if ($moduloEstado !== null) {
            return $this->sendResponse(null, 'Estado creado');
        }

        return $this->sendResponse(null, 'Error al crear', 405);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $obj = ModuloEstados::find($id);
        if ($obj) {
            return $this->sendResponse(ModuloEstadoResoure::make($obj), "success");
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validate = Validator::make($request->all(), [
            'descripcion' => "required|string|unique:modulo_estados,descripcion$id",
        ]);
        if ($validate->fails()) {
            return $this->sendResponse(null, $validate->errors()->first());
        }
        $input = $request->all();
        $update = ModuloEstados::find($id)->update($input);
        if ($update) {
            return $this->sendResponse(null, 'Estado actualizado');
        }
        return $this->sendResponse(null, 'Eerror al actualizar', 405);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $obj = ModuloEstados::find($id);
            if ($obj) {
                $obj->delete();
                return $this->sendResponse(null, 'Estado eliminado');
            }
            return $this->sendResponse(null, 'No se encontro informacion', 404);
        } catch (\Exception $e) {
            return $this->sendResponse(null, 'Estado no disponible para eliminar', 500);
        }
    }
}
