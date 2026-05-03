<?php

namespace App\Http\Middleware;

use App\Interfaces\Config\DeviceService;
use App\Interfaces\Config\MatchTokensService;
use App\Utils\DeviceUtility;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MatchTokenMiddleware
{
    public function __construct(
        private MatchTokensService $matchTokensService,
        private DeviceUtility $deviceUtility
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $device = $this->deviceUtility->get_DeviceInfo($request);

        if ($token==null || $device==null) {
            return $this->sendResponse(null, 'No se proporciona informacion del dispositivo', 401);
        }

        $match = $this->matchTokensService->whereFirst([
            ['token', '=', $token],
            ['device', '=', $device['id']]
        ]);

        if (!$match) {
            return $this->sendResponse(null, 'Token Incorrecto', 401);
        }

        return $next($request);
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
