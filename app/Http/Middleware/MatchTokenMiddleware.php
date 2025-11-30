<?php

namespace App\Http\Middleware;

use App\Models\Devices;
use App\Models\MatchTokens;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MatchTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $deviceId = $this->getdeviceInfo($request);

        if (empty($token) || empty($deviceId)) {
            return $this->sendResponse(null, 'No se proporciona informacion del dispositivo2', 401);
        }
        $match = MatchTokens::where('token', $token)
            ->where('device', $deviceId['id'])
            ->first();

        if (!$match) {
            return $this->sendResponse(null, 'Token Incorrecto', 401);
        }

        return $next($request);
    }


    private function getdeviceInfo(Request $request): array
    {
        $info = [
            'ip' => $request->header('X-Device-Ip'),
            'ip2' => $request->ip(),
            'name' => $request->header('X-Device-Name'),

        ];

        $device = Devices::where('ip', hash('sha256', $info['ip']))
            ->where('ip2', hash('sha256', $info['ip2']))
            ->where('name',  hash('sha256', $info['name']))
            ->first();

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
