<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActionsVistasResource;
use App\Models\ActionsVistas;
use App\Models\Vistas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ActionsVistasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // return not implemented
        return  $this->sendResponse(null, 'Not implemented', 501);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'vista.id' => 'required|integer',
            'codigo' => 'required|string|max:100',
            'nombre' => 'required|string|max:150',
        ]);
        if ($validate->fails()) {
            return $this->sendResponse(false, $validate->errors()->first(), 422);
        }

        // Check if the vista exists
        $vistaId = $request->input('vista.id');
        $vista = Vistas::find($vistaId);
        if (!$vista) {
            return $this->sendResponse(false, 'Vista no encontrada', 404);
        }
        $codigovista_model = $vista->codigo;
        if (!$codigovista_model) {
            return $this->sendResponse(false, 'Vista no tiene codigo', 422);
        }

        $data = [
            'vista' => $request->input('vista.id'),
            'codigo' => $codigovista_model . strtoupper($request->input('codigo')),
            'nombre' => $request->input('nombre'),
        ];

        $existingAction = ActionsVistas::where('codigo', $data['codigo'])->first();
        if ($existingAction) {
            return $this->sendResponse(false, 'El codigo de la accion ya existe', 422);
        }


        $actionVista = ActionsVistas::create($data);
        if ($actionVista) {
            return $this->sendResponse(true, 'Action Creada', 200);
        } else {
            return $this->sendResponse(false, 'Error al crear Action', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $actionVista = ActionsVistas::find($id);
        if ($actionVista) {
            return $this->sendResponse(ActionsVistasResource::make($actionVista), 'Action Vista', 200);
        } else {
            return $this->sendResponse(null, 'Action no encontrada', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return  $this->sendResponse(null, 'Not implemented', 501);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $actionVista = ActionsVistas::find($id);
        if ($actionVista) {
            try {
                $actionVista->delete();
                return $this->sendResponse(true, 'Action eliminada', 200);
            } catch (\Exception $e) {
                return $this->sendResponse(false, 'Accion no disponible para eliminar', 500);
            }
        } else {
            return $this->sendResponse(false, 'Action no encontrada', 404);
        }
    }

    public function findByVista(string $vistaId)
    {
        $actions = ActionsVistas::where('vista', $vistaId)->get();
        if ($actions->isEmpty()) {
            return $this->sendResponse(null, 'No se encontraron acciones para esta vista', 404);
        }
        return $this->sendResponse(ActionsVistasResource::collection($actions), 'Acciones de la vista', 200);
   
    }
}
