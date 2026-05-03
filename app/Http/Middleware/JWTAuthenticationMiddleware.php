<?php

namespace App\Http\Middleware;

use App\Models\MatchTokens;
use App\Utils\DeviceUtility;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use App\Utils\Services\CryptoService;

class JWTAuthenticationMiddleware
{


    public function __construct(
        private CryptoService $encService,
        private DeviceUtility $deviceUtility
        )
    {

    }

    public function handle(Request $request, Closure $next)
    {
        $encryptedToken = $this->getTokenFromRequest($request);
        try {

            if (!$encryptedToken) {
                return $this->sendResponse(null, 'Token no proporcionado', 401);
            }
            $deviceInfo = $this->deviceUtility->getSingleInfo($request);

            if (!$deviceInfo) {
                return $this->sendResponse(null, 'Informacion del dispositivo no proporcionada', 401);
            }

            $token = $this->encService->decrypt($encryptedToken, $deviceInfo['ip']);

            if (!$token) {
                return $this->sendResponse(null, 'Error al verificar el token', 401);
            }

            if (!$user = JWTAuth::setToken($token)->authenticate()) {
                return $this->sendResponse(null, 'Usuario no encontrado', 401);
            }

            $request->attributes->set('auth', $user);
            $request->setUserResolver(fn() => $user);

            return $next($request);
        } catch (TokenExpiredException $e) {
            MatchTokens::where('token', $encryptedToken)->delete();
            return $this->sendResponse(null, 'Sesion expirada', 401);
        } catch (TokenInvalidException $e) {
            return $this->sendResponse(null, 'Token invalido', 401);
        } catch (JWTException $e) {
            return $this->sendResponse(null, 'Token no proporcionado', 401);
        }
    }

    private function getTokenFromRequest(Request $request)
    {
        if ($request->hasHeader('Authorization')) {
            $header = $request->header('Authorization');
            if (str_starts_with($header, 'Bearer ')) {
                return substr($header, 7);
            }
        }
        return $request->input('token');
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
