<?php

namespace App\Http\Middleware;

use App\Interfaces\Config\DeviceService;
use App\Interfaces\Config\MatchTokensService;
use App\Services\EncryptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MatchTokenMiddleware
{
    public function __construct(
        private DeviceService $deviceService,
        private MatchTokensService $matchTokensService,
        private EncryptionService $encService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $deviceId = $this->getDeviceInfo($request);

        if (empty($token) || empty($deviceId)) {
            return $this->sendResponse(null, 'No se proporciona informacion del dispositivo', 401);
        }

        $match = $this->matchTokensService->whereFirst([
            ['token', '=', $token],
            ['device', '=', $deviceId['id']]
        ]);

        if (!$match) {
            return $this->sendResponse(null, 'Token Incorrecto', 401);
        }

        return $next($request);
    }


    private function getDeviceInfo(Request $request): array
    {
        $info = [
            'ip' => $request->header('X-Device-Ip'),
            'ip2' => $request->ip(),
            'name' => $request->header('X-Device-Name'),

        ];

        $device = $this->deviceService->whereFirst([
            ['ip', '=', $this->encService->genHash($info['ip'])],
            ['ip2', '=', $this->encService->genHash($info['ip2'])],
            ['name', '=', $this->encService->genHash($info['name'])]
        ]);

        if ($device) {
            return ['id' => $device->id];
        }
        return [];
    }
    private function sendResponse($result, $message, $code)
    {
        $response = [
            'message' => $message,
            'code' => $code,
            'data' => $result,
        ];
        return response()->json($response, $code);
    }
}
