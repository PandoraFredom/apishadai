<?php

namespace App\Http\Middleware;

use App\Interfaces\Config\AppConfigService;
use App\Services\EncryptionService;
use Closure;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AppVersionSecurityMiddleware
{

    public function __construct(private AppConfigService $appConfigService, private EncryptionService $encryptionService) {}


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

        $dat = $this->appConfigService->existVersion($this->encryptionService->genHash($info['version']));

        if (!$dat) {
            return $this->sendResponse(null, 'Version de app Incorrecta', 401);
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
