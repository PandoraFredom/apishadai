<?php

namespace App\Http\Middleware;

use App\Interfaces\Config\AppConfigService;
use App\Utils\Services\SingleHashService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppVersionSecurityMiddleware
{

    public function __construct(private AppConfigService $appConfigService, private SingleHashService $encryptionService) {}


    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $info = $this->getAppVersion($request);
        // Log::info('AppVersionSecurityMiddleware: handle', ['info' => $this->encryptionService->genHash($info['version'])]);
        if (empty($info)) {
            return $this->sendResponse(null, 'No se proporciona la version de la app', 401);
        }

        $dat = $this->appConfigService->existVersion($info['version']);

        if (!$dat) {
            return $this->sendResponse(null, 'Version de app Incorrecta', 426);
        }

        return $next($request);
    }


    private function getAppVersion(Request $request): array
    {

        if ($request->hasHeader('App-Version')) {
            return ['version' => $request->header('App-Version')];
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
