<?php

namespace App\Http\Middleware;

use App\Models\Device;
use App\Models\UknowDevices;
use App\Utils\DeviceUtility;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceSecurityMiddleware
{
    public function __construct(
        private DeviceUtility $deviceUtility

    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $device = $request->attributes->get('authenticated_device');

        if (! $device instanceof Device) {
            $device = $this->deviceUtility->get_DeviceInfo($request);
        }

        if (! $device) {
            $deviceInfo = $this->deviceUtility->getSingleInfo($request);

            if (! $deviceInfo) {
                return $this->sendResponse(null, 'Informacion del dispositivo no proporcionada', 401);
            }
            // limpir tabla
            UknowDevices::truncate();
            UknowDevices::create($deviceInfo);

            return $this->sendResponse(null, 'Dispositivo no registrado, consultar con el administrador:', 401);
        }

        $status = $device->Estado->descripcion ?? null;
        if ($status !== 'ACTIVO') {
            return $this->sendResponse(null, 'Dispositivo desactivado\nconsultar con el administrador', 401);
        }

        $request->attributes->set('authenticated_device', $device);

        return $next($request);
    }

    /**
     * Envía una respuesta JSON normalizada
     */
    private function sendResponse(?array $result, string $message, int $code)
    {
        return response()->json([
            'message' => $message,
            'code' => $code,
            'data' => $result,
        ], $code);
    }
}
