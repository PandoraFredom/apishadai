<?php

namespace App\Http\Middleware;

use App\Interfaces\Config\DeviceService;
use App\Services\EncryptionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DeviceSecurityMiddleware
{

    public function __construct(
        private EncryptionService $encService,
        private DeviceService $deviceService
    ) {}

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
        $device = $this->deviceService->whereFirst([
            'ip' => $this->encService->genHash($info['ip']),
            'ip2' => $this->encService->genHash($request->ip()),
            'name' => $this->encService->genHash($info['name']),
        ]);
       // Log::info("Device Info - IP: {$this->encService->genHash($info['ip'])}, IP2: {$this->encService->genHash($request->ip())}, Name: {$this->encService->genHash($info['name'])}");
        if (!$device) {
            return $this->sendResponse(null, 'Dispositivo no registrado', 401);
        }

        $status = $device->Estado->descripcion ?? null;
        if ($status !== 'ACTIVO') {
            return $this->sendResponse(null, 'Dispositivo desactivado,consultar con el administrador', 401);
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
