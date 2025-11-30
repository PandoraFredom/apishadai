<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VistaEstados;
use App\Http\Resources\VistaEstadosResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VistaEstadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = VistaEstados::all();
        if ($list->count() > 0) {
            return $this->sendResponse(VistaEstadosResource::collection($list), 'success');
        }
        return $this->sendResponse(null, 'No se encontraron informacion', 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'descripcion' => 'required|string|unique:vista_estados,descripcion',
        ]);

        if ($validate->fails()) {
            return $this->sendResponse(null, $validate->errors(), 422);
        }
        try {
            $list = VistaEstados::create($request->all());
            if ($list) {
                return $this->sendResponse(null, 'Estado creado');
            }
            return $this->sendResponse(null, 'No se pudo crear la informacion', 500);
        } catch (\Throwable $e) {
            return $this->sendResponse(null, 'No se pudo crear la informacion', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $obj = VistaEstados::find($id);
        if ($obj) {
            return $this->sendResponse(VistaEstadosResource::make($obj), "success");
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }
}
