<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ModuloEstadoResoure;
use App\Interfaces\Config\ModuloEstadoService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ModuloEstadoController extends Controller
{
    public function __construct(private readonly ModuloEstadoService $service) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {

        $list = $this->service->getAll();

        if (!$list) {
            return $this->sendResponse(null, 'No se encontraron modulos', 404);
        }

        return $this->sendResponse(ModuloEstadoResoure::collection($list), 'success');
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
            $moduloEstado = $this->service->create($data);
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
        $obj = $this->service->findById((int) $id);
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
        $update = $this->service->update((int) $id, $input);
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
            $obj = $this->service->findById((int) $id);
            if ($obj) {
                $this->service->delete((int) $id);
                return $this->sendResponse(null, 'Estado eliminado');
            }
            return $this->sendResponse(null, 'No se encontro informacion', 404);
        } catch (\Exception $e) {
            return $this->sendResponse(null, 'Estado no disponible para eliminar', 500);
        }
    }
}
