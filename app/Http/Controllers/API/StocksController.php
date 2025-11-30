<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\StocksResource;
use App\Models\Stocks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StocksController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = Stocks::all();
        if ($list->count() > 0) {
            return $this->sendResponse(StocksResource::collection($list), 'success');
        }
        return $this->sendResponse(null, 'No se encontraron informacion', 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'descripcion' => 'required|string|unique:stocks,descripcion',
            'telefono' => 'required|string',
            'ubicacion' => 'required|string',
        ]);

        if ($validate->fails()) {
            return $this->sendResponse(null, 'Validator error: ' . $validate->errors()->first(), 400);
        }

        try {
            $stock = Stocks::create($request->all());
            if ($stock) {
                return $this->sendResponse(true, 'Stock creado');
            }
            return $this->sendResponse(false, 'No se pudo crear la informacion', 500);
        } catch (\Throwable $e) {
            return $this->sendResponse(false, 'No se pudo crear la informacion', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $obj = Stocks::find($id);
        if ($obj) {
            return $this->sendResponse(StocksResource::make($obj), "success");
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'descripcion' => "required|string|unique:stocks,descripcion," . $request->id,
            'telefono' => 'required|string',
            'ubicacion' => 'required|string',
        ]);

        if ($validate->fails()) {
            return $this->sendResponse(null, 'Validator error: ' . $validate->errors()->first(), 400);
        }

        try {
            $input = $request->all();
            $update = Stocks::find($request->id)->update($input);
            if ($update) {
                return $this->sendResponse(true, 'Stock actualizado');
            }
            return $this->sendResponse(false, 'No se pudo actualizar la informacion', 500);
        } catch (\Throwable $e) {
            return $this->sendResponse(false, 'No se pudo actualizar la informacion', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $obj = Stocks::find($id);
            if ($obj) {
                $obj->delete();
                return $this->sendResponse(true, 'Stock eliminado',200);
            }
            return $this->sendResponse(false, 'No se encontro informacion', 404);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'Stock no disponible para eliminar', 500);
        }
    }
}
