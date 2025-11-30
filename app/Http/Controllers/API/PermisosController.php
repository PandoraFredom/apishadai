<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermisosResource;
use App\Models\Permisos;
use App\Models\TipoTiempo;
use Carbon\Carbon;
use Carbon\Traits\Timestamp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermisosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'usuario.id' => 'required|integer|exists:users,id',
            'modulo.id' => 'required|integer|exists:modulos,id',
            'vista.id' => 'required|integer|exists:vistas,id',
            'actionvista.id' => 'required|integer|exists:actionsvistas,id',
            'tipo_tiempo.id' => 'required|integer|exists:tipos_tiempo,id',
        ]);

        if ($validate->fails()) {
            return $this->sendResponse(null, $validate->errors()->first(), 422);
        }

        if ($this->checkExistPermiso(
            $request->usuario['id'],
            $request->modulo['id'],
            $request->vista['id'],
            $request->actionvista['id']
        )) {
            return $this->sendResponse(false, 'El permiso ya existe', 422);
        }
        try {
            $tipoTiempo = TipoTiempo::find($request->tipo_tiempo['id']);

            if (!$tipoTiempo) {
                return $this->sendResponse(false, 'Tipo de tiempo no encontrado', 404);
            }
            $lifetime = $this->get_lifetime($tipoTiempo);

            if (!$lifetime) {
                return $this->sendResponse(false, 'Error al calcular el tiempo de vida', 500);
            }

            $data = [
                'usuario' => $request->usuario['id'],
                'modulo' => $request->modulo['id'],
                'vista' => $request->vista['id'],
                'actionvista' => $request->actionvista['id'],
                'lifetime' => $lifetime,
                'tipo_tiempo' => $request->tipo_tiempo['id'],
            ];

            $permiso = Permisos::create($data);
            if ($permiso) {
                return $this->sendResponse(true, 'Permiso creado');
            }
            return $this->sendResponse(false, 'No se pudo crear la informacion1', 500);
        } catch (\Throwable $e) {
            return $this->sendResponse(false, 'No se pudo crear la informacion2:' . $e, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $obj = Permisos::find($id);
        if ($obj) {
            return $this->sendResponse(PermisosResource::make($obj), "success");
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return $this->sendResponse(false, 'Not implemented', 501);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $obj = Permisos::find($id);
        if ($obj) {
            try {
                $obj->delete();
                return $this->sendResponse(true, 'Permiso eliminado');
            } catch (\Exception $e) {
                return $this->sendResponse(false, 'Permiso no disponible para eliminar', 500);
            }
        }
        return $this->sendResponse(false, 'No se encontro informacion', 404);
    }


    public function findbyuser($id)
    {
        $list = Permisos::where('usuario', $id)->get();
        if ($list->count() > 0) {
            return $this->sendResponse(PermisosResource::collection($list), "success");
        }
        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    private function checkExistPermiso($userid, $moduloid, $vistaid, $actionid)
    {
        return Permisos::where('usuario', $userid)
            ->where('modulo', $moduloid)
            ->where('vista', $vistaid)
            ->where('actionvista', $actionid)
            ->exists();
    }



    private function get_lifetime($tipoTiempo)
    {
        $carbon = Carbon::now();
        $cantidad = (int) $tipoTiempo->cantidad;
        $fecha =  match ($tipoTiempo->unidad) {
            'minutes' => $carbon->addMinutes($cantidad),
            'hours' => $carbon->addHours($cantidad),
            'days' => $carbon->addDays($cantidad),
            'weeks' => $carbon->addWeeks($cantidad),
            'months' => $carbon->addMonths($cantidad),
            'years' => $carbon->addYears($cantidad),
            'indefinido'=> Carbon::create(2099, 12, 31, 23, 59, 59),
            default => Carbon::now(),
        };

        return $fecha->timestamp;

    }
}
