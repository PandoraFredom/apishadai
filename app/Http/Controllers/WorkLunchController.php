<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorkLunchResource;
use App\Models\Device;
use App\Models\WorkLunch;
use App\Services\EncryptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkLunchController extends Controller
{
    private  $encService;
    public function __construct(EncryptionService $encryptionService)
    {
        $this->encService = $encryptionService;
    }
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
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $obj = WorkLunch::find($id);
        if ($obj) {
            return $this->sendResponse(WorkLunchResource::make($obj), "success");
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

    public function work(Request $request)
    {
        try {
            $decrypteduser = $this->encService->decrypt($request->usuario);
            $request->merge(['usuario' => $decrypteduser]);



            $validator = Validator::make($request->all(), [
                'usuario' => "required|exists:users,id",
                'date_time' => 'required|date_format:Y-m-d H:i:s',
            ]);

            if ($validator->fails()) {
                return $this->sendResponse(null,  $validator->errors()->first(), 400);
            }
            $date = Carbon::parse($request->date_time)->toDateString();

            $workSession = Worklunch::where('usuario', $request->usuario)
                ->whereDate('wkstart_time', $date)
                ->first();
            if ($workSession) {
                if ($workSession->wkstart_time && $workSession->wkend_time) {
                    return $this->sendResponse(null, 'Datos del dia ya registrados', 400);
                }

                if (!$workSession->wkend_time) {
                    $workSession->wkend_time = $request->date_time;
                    $workSession->save();
                    $workSession->sendAlert();
                    return $this->sendResponse(null, 'Salida Exitosa');
                }
            } else {
                $device = $this->getDeviceInfo($request);
                if (!$device) {
                    return $this->sendResponse(null, 'No se encontro dispositivo', 500);
                }


                $workSession = Worklunch::create([
                    'usuario' => $request->usuario,
                    'device' => $device->id,
                    'wkstart_time' => $request->date_time,
                ]);

                if ($workSession) {
                  $workSession->sendAlert();
                    return $this->sendResponse(null, 'Registro Exitoso');
                }
            }
            return $this->sendResponse(null, 'No se pudo registrar1', 500);
        } catch (\Throwable $e) {
            return $this->sendResponse(null, 'No se pudo registrar:'.$e->getMessage(), 500);
        }
    }

    public function lunch(Request $request)
    {
        $decrypteduser = $this->encService->decrypt($request->usuario);
        $request->merge(['usuario' => $decrypteduser]);

        $validator = Validator::make($request->all(), [
            'usuario' => 'required|exists:users,id',
            'date_time' => 'required|date|date_format:Y-m-d H:i:s',
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 400);
        }

        $date = Carbon::parse($request->date_time)->toDateString();

        $workSession = Worklunch::where('usuario', $request->usuario)
            ->whereDate('wkstart_time', $date)
            ->first();

        if (!$workSession) {
            return $this->sendResponse(null, 'No se encontró una sesión de trabajo para este día', 400);
        }

        if ($workSession->lunch_start_time && $workSession->lunch_end_time) {
            return $this->sendResponse(null, 'Datos del día ya registrados', 400);
        }

        if ($workSession->lunch_start_time) {

            $workSession->lunch_end_time = $request->date_time;
        } else {
            $workSession->lunch_start_time = $request->date_time;
        }
     
        $workSession->save();

        if ($workSession) {
            $workSession->sendAlert();
            return $this->sendResponse(null, 'Registro Exitoso');
        }

        return $this->sendResponse(null, 'No se pudo registrar', 500);
    }
    public function findbyuserdate(Request $request)
    {
        // Validar que el campo filterItem sea un array y que contenga los elementos necesarios
        $validator = Validator::make($request->all(), [
            'filterItem' => 'required|array',
            'filterItem.*.key' => 'required|string|in:usuario,start_date,end_date',
            'filterItem.*.value' => 'required',
        ]);
    
        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 400);
        }
    
        // Convertir el array filterItem en un formato más manejable
        $filters = collect($request->filterItem)->pluck('value', 'key');
    
        // Validar que los valores específicos existan y tengan el formato correcto
        $validator = Validator::make($filters->toArray(), [
            'usuario' => 'required|exists:users,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ]);
    
        if ($validator->fails()) {
            return $this->sendResponse(null, $validator->errors()->first(), 400);
        }
    
        // Obtener los valores de los filtros
        $usuario = $filters['usuario'];
        $startDate = Carbon::createFromFormat('Y-m-d', $filters['start_date'])->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $filters['end_date'])->endOfDay();
    
        // Realizar la consulta
        $workSessions = ($filters['start_date'] === $filters['end_date']) ? Worklunch::where('usuario', $usuario)
            ->whereDate('wkstart_time', $startDate)->get() : Worklunch::where('usuario', $usuario)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('wkstart_time', [$startDate, $endDate])
                        ->orWhereBetween('wkend_time', [$startDate, $endDate]);
                })->get();
    
        return $this->sendResponse(WorklunchResource::collection($workSessions), 'Worklunch retrieved successfully');
    }

    private function getDeviceInfo(Request $request)
    {
        $DeviceName = $request->header('X-Device-Name');
        $DeviceIp = $request->header('X-Device-Ip');
        $device = Device::where('name', hash('sha256', $DeviceName))
            ->where('ip', hash('sha256', $DeviceIp))
            ->where('ip2', hash('sha256', $request->ip()))
            ->first();
        return $device;
    }
}
