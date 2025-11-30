<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserEstadosResource;
use App\Models\UsuarioEstado;
use Illuminate\Http\Request;

class UserEstadosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = UsuarioEstado::all();
        if ($list->count() > 0) {
            return $this->sendResponse(UserEstadosResource::collection($list), 'success');
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
        $item = UsuarioEstado::find($id);
        if ($item) {
            return $this->sendResponse(UserEstadosResource::make($item), 'success');
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
