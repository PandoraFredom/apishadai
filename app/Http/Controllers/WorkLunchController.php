<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorkLunchResource;
use App\Interfaces\Config\DeviceInfoService;
use App\Interfaces\WorkLunch\WorkLunchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkLunchController extends Controller
{
    public function __construct(
        private readonly DeviceInfoService $deviceInfoService,
        private readonly WorkLunchService $workLunchService
    ) {}

    public function today()
    {
        $session = $this->workLunchService->todayForUser(
            (int) Auth::id(),
            now()->toDateString(),
        );

        return $this->sendResponse(
            $session ? WorkLunchResource::make($session) : null,
            $session ? 'Jornada laboral consultada.' : 'Aún no has registrado la entrada de hoy.',
        );
    }

    public function index()
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    public function store(Request $request)
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    public function show(string $id)
    {
        $obj = $this->workLunchService->findById((int) $id);
        if ($obj) {
            return $this->sendResponse(WorkLunchResource::make($obj), 'success');
        }

        return $this->sendResponse(null, 'No se encontro informacion', 404);
    }

    public function update(Request $request, string $id)
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    public function destroy(string $id)
    {
        return $this->sendResponse(null, 'Not implemented', 501);
    }

    public function work(Request $request)
    {
        try {
            $deviceId = $this->deviceInfoService->authenticatedDeviceId($request);
            if ($deviceId === null) {
                return $this->sendResponse(null, 'Dispositivo no válido.', 401);
            }

            $session = $this->workLunchService->workService(
                (int) Auth::id(),
                now()->format('Y-m-d H:i:s'),
                $deviceId
            );
            $message = $session->wkend_time
                ? 'Salida registrada correctamente.'
                : 'Entrada registrada correctamente.';

            return $this->sendResponse(WorkLunchResource::make($session), $message);
        } catch (\DomainException $e) {
            return $this->sendResponse(null, $e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->logError(__METHOD__, $e);

            return $this->sendResponse(null, 'No se pudo registrar la jornada laboral.', 500);
        }
    }

    public function lunch(Request $request)
    {
        try {
            $session = $this->workLunchService->lunchService(
                (int) Auth::id(),
                now()->format('Y-m-d H:i:s')
            );
            $message = $session->lunch_end_time
                ? 'Fin de almuerzo registrado correctamente.'
                : 'Inicio de almuerzo registrado correctamente.';

            return $this->sendResponse(WorkLunchResource::make($session), $message);
        } catch (\DomainException $e) {
            return $this->sendResponse(null, $e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->logError(__METHOD__, $e);

            return $this->sendResponse(null, 'No se pudo registrar el almuerzo.', 500);
        }
    }
}
