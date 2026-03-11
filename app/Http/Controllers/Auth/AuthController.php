<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Interfaces\Auth\AuthService;
use App\Utils\DeviceUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService,private DeviceUtility $deviceUtility) {}


    public function login(LoginRequest $request)
    {
        try {
            $deviceInfo = $this->deviceUtility->get_DeviceInfo($request);

            if ($deviceInfo == null) {
                return $this->sendResponse(null, "Error al obtener la informacion del dispositivo", 401);
            }

            $credentials = $request->only('name', 'password');
            $credentials['name'] = $this->authService->hashValue($credentials['name']);

            if (!$token = Auth::attempt($credentials)) {
                return $this->sendResponse(null, 'Credenciales incorrectas.', 401);
            }
            $permisos = $this->authService->getPermisosUser(Auth::user()->id);

            if ($permisos->isEmpty()) {
                return $this->sendResponse(null, 'No hay permisos disponibles para el usuario', 500);
            }
            $encToken = $this->authService->encryptToken($token);

            $this->authService->deleteMatchTokenUser(Auth::user()->id);

            $data = [
                'usuario' => Auth::user()->id,
                'device' => $deviceInfo->id,
                'token' => $encToken
            ];
            $savematch = $this->authService->createMatchToken($data);
            if (!$savematch) {
                return $this->sendResponse(null, 'Error al crear login:1', 500);
            }


            $data = [
                'token' => $encToken,
                'uname' => Auth::user()->nombre,
                'urol' => Auth::user()->Rol->descripcion,
                'stockname' => $deviceInfo->Stock->descripcion,
                'stockid' => $deviceInfo->Stock->id,
                'deviceid' => $deviceInfo->id,
                'spermisos' => $permisos,
            ];

            return $this->sendResponse($data, 'login success');
        } catch (\Exception $e) {
            Log::error('Error en login: ' . $e->getMessage(), ['exception' => $e]);
            return $this->sendResponse(null, 'Error al crear login:3::' . $e->getMessage(), 500);
        }
    }





    public function logout(Request $request)
    {
        try {
            $decryptedToken = $this->authService->getDecryptedToken($request);

            if ($decryptedToken) {
                JWTAuth::invalidate($decryptedToken);

                if ($request->hasHeader('Authorization')) {
                    $header = $request->header('Authorization');
                    if (str_starts_with($header, 'Bearer ')) {
                        $token = substr($header, 7);
                        $this->authService->deleteMatchTokenByToken($token);
                    }
                }

                Auth::logout();
                return $this->sendResponse(true, 'logout success');
            }

            return $this->sendResponse(false, 'Token no válido', 401);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'logout failed:' . $e->getMessage(), 500);
        }
    }
}
