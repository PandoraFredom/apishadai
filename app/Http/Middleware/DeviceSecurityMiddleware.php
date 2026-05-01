<?php

namespace App\Http\Middleware;

use App\Utils\DeviceUtility;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceSecurityMiddleware
{

    public function __construct(
        private DeviceUtility $deviceUtility,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {

        $device = $this->deviceUtility->get_DeviceInfo($request);

       /* $data = [
            'ip' => $this->encService->genHash($device['ip'] ?? ''),
            'name' => $this->encService->genHash($device['name'] ?? ''),
        ];*/

        if (!$device) {
            return $this->sendResponse(null, "Dispositivo no registrado, consultar con el administrador" , 401);
        }

        $status = $device->Estado->descripcion ?? null;
        if ($status !== 'ACTIVO') {
            return $this->sendResponse(null, 'Dispositivo desactivado,consultar con el administrador', 401);
        }

        return $next($request);
    }


    /**
     * Envía una respuesta JSON normalizada
     */
    private function sendResponse($result, $message, $code)
    {
        return response()->json([
            'message' => $message,
            'code' => $code,
            'data' => $result,
        ], $code);
    }
}
