<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PromocionesResource;
use App\Models\Promociones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PromocionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // ultimos 15 registros
        $list = Promociones::orderBy('id', 'desc')->take(15)->get();

        if ($list->isEmpty()) {
            return $this->sendResponse(null, 'No hay datos para mostrar', 404);
        }
        return $this->sendResponse(PromocionesResource::collection($list), 'Lista de promociones', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'estado.id' => 'required|integer|exists:promoestado,id',
        ]);
        if ($validator->fails()) {
            return $this->sendResponse(false, $validator->errors()->first(), 422);
        }
        $input = [
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'fecha_inicio' =>  date('Y-m-d', strtotime($request->fecha_inicio)),
            'fecha_fin' => date('Y-m-d', strtotime($request->fecha_fin)),
            'estado' => $request->estado['id'],
        ];

        $promocion = Promociones::create($input);
        if ($promocion) {
            return $this->sendResponse(true, 'Promocion creada');
        }
        return $this->sendResponse(false, 'No se pudo crear la informacion', 500);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $promocion = Promociones::find($id);
        if (!$promocion) {
            return $this->sendResponse(null, 'No se encontro la promocion', 404);
        }
        return $this->sendResponse(PromocionesResource::make($promocion), 'Promocion encontrada', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $validator = Validator::make($request->all(), [
            'estado.id' => 'required|integer|exists:promoestado,id',
        ]);
        if ($validator->fails()) {
            return $this->sendResponse(false, $validator->errors()->first(), 422);
        }
        $promocion = Promociones::find($id);
        if (!$promocion) {
            return $this->sendResponse(false, 'No se encontro la promocion', 404);
        }
        $input = $request->all();
        $input['estado'] = $input['estado']['id'];
        $promocion->update($input);
        if ($promocion) {
            return $this->sendResponse(true, 'Promocion actualizada');
        } else {
            return $this->sendResponse(false, 'No se pudo actualizar la informacion', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        $promocion = Promociones::find($id);
        if (!$promocion) {
            return $this->sendResponse(false, 'No se encontro la promocion', 404);
        }
        if ($promocion->delete()) {
            return $this->sendResponse(true, 'Promocion eliminada');
        }
        return $this->sendResponse(false, 'No se pudo eliminar la informacion', 500);
    }



    public function getactive()
    {
        $promocion = Promociones::join('promoestado', 'promoestado.id', '=', 'promociones.estado')
            ->where('promoestado.descripcion', 'ACTIVO')->get(['promociones.*'])
            ->first();
        if (!$promocion) {
            return $this->sendResponse(null, 'No hay promociones activas', 404);
        }

        return $this->sendResponse(PromocionesResource::make($promocion), 'Promocion activa');
    }

    public function filter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filterItem' => 'required|array',
            'filterItem.*.key' => 'required|string',
            'filterItem.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 422);
        }

        // filtrar por nombre like  
        $query = Promociones::query();
        foreach ($request->filterItem as $item) {
            switch ($item['key']) {
                case 'nombre':
                    $query->where($item['key'], 'like', '%' . $item['value'] . '%');
                    break;
                default:
                    $query->where($item['key'], $item['value']);
                    break;
            }
        }
        $list = $query->get();
        if ($list->isEmpty()) {
            return $this->sendResponse(null, 'No hay datos para mostrar', 404);
        }
        return $this->sendResponse(PromocionesResource::collection($list), 'Lista de promociones', 200);
    }
}
