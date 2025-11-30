<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeviceEstadoResource;
use App\Models\DeviceEstado;
use Illuminate\Http\Request;

class DeviceEstadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = DeviceEstado::all();
        if ($list->count() > 0) {
            return $this->sendResponse(DeviceEstadoResource::collection($list), 'success');
        }
        return $this->sendResponse(null, 'No se encontraron informacion', 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $obj = DeviceEstado::find($id);
        if ($obj) {
            return $this->sendResponse(DeviceEstadoResource::make($obj), "success");
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
