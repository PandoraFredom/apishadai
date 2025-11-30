<?php

namespace App\Http\Middleware;

use App\Models\MatchTokens;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use App\Services\EncryptionService;

class JWTAuthenticationMiddleware
{
    protected $ipEncryption;

    public function __construct(EncryptionService $ipEncryption)
    {
        $this->ipEncryption = $ipEncryption;
    }

    public function handle(Request $request, Closure $next)
    {
        try {

            $encryptedToken = $this->getTokenFromRequest($request);

            if (!$encryptedToken) {
                return $this->sendResponse(null, 'Token no proporcionado', 401);
            }

            $token = $this->ipEncryption->decrypt($encryptedToken);

            if (!$token) {
                return $this->sendResponse(null, 'Error al verificar el token', 401);
            }

            if (!$user = JWTAuth::setToken($token)->authenticate()) {
                return $this->sendResponse(null, 'Usuario no encontrado', 401);
            }

            $request->auth = $user;

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

    private function getTokenFromRequest(Request $request): ?string
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
