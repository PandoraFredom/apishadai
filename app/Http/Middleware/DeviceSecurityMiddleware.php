<?php

namespace App\Http\Middleware;

use App\Models\Device;
use App\Services\EncryptionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class DeviceSecurityMiddleware
{

    public function __construct(private EncryptionService $encService)
    {


    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $info = $this->getIpAndDeviceName($request);

        if (empty($info)) {
            return $this->sendResponse(null, 'No se proporciona información del dispositivo', 401);
        }

        // Buscar el dispositivo que coincida con ip, ip2 y name hasheados
        $device = Device::with('estado')
            ->where('ip', $this->encService->genHash($info['ip']))
            ->where('ip2', $this->encService->genHash($request->ip()))
            ->where('name', $this->encService->genHash($info['name']))
            ->first();

        if (!$device) {
            return $this->sendResponse(null, 'Dispositivo no registrado:' . $this->encService->genHash($info['name']), 401);
        }

        $status = $device->Estado->descripcion ?? null;
        if ($status !== 'ACTIVO') {
            return $this->sendResponse(null, 'Dispositivo no activo', 401);
        }

        return $next($request);
    }

    /**
     * Obtiene la IP y nombre del dispositivo desde los headers
     */
    private function getIpAndDeviceName(Request $request): array
    {
        if ($request->hasHeader('X-Device-Ip') && $request->hasHeader('X-Device-Name')) {
            return [
                'ip' => $request->header('X-Device-Ip'),
                'name' => $request->header('X-Device-Name'),
            ];
        }
        return [];
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
