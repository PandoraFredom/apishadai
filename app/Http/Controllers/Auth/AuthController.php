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
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private DeviceUtility $deviceUtility,

    ) {}


    public function login(LoginRequest $request)
    {
        $stage = 'inicio';
        $errorRef = uniqid('login_', true);

        try {
            $stage = 'dispositivo';
            $deviceInfo = $this->deviceUtility->get_DeviceInfo($request);

            if ($deviceInfo == null) {
                return $this->sendResponse(null, "Error al obtener la informacion del dispositivo", 401);
            }

            $stock = $deviceInfo->Stock;

            if (!$stock) {
                Log::warning('Login rechazado: dispositivo sin stock relacionado', [
                    'ref' => $errorRef,
                    'device_id' => $deviceInfo->id,
                ]);

                return $this->sendResponse(null, 'El dispositivo no tiene stock asignado', 401);
            }

            $stage = 'credenciales';
            $credentials = $request->validated();

            $credentials['name'] = $this->authService->hashValue($credentials['name']);

            if (!$token = Auth::attempt($credentials)) {
                return $this->sendResponse(null, 'Credenciales incorrectas.', 401);
            }

            $user = Auth::user();

            if (!$user) {
                Log::warning('Login rechazado: Auth::attempt genero token sin usuario autenticado', [
                    'ref' => $errorRef,
                ]);

                return $this->sendResponse(null, 'No se pudo autenticar el usuario', 401);
            }

            $rol = $user->Rol;

            if (!$rol) {
                Log::warning('Login rechazado: usuario sin rol relacionado', [
                    'ref' => $errorRef,
                    'user_id' => $user->id,
                ]);

                return $this->sendResponse(null, 'El usuario no tiene rol asignado', 401);
            }

            $stage = 'permisos';
            $permisos = $this->authService->getPermisosUser($user->id);

            if ($permisos->isEmpty()) {
                return $this->sendResponse(null, 'No hay permisos disponibles para el usuario', 500);
            }

            $stage = 'token';
            $encToken = $this->authService->encryptToken($token, $request);

            $stage = 'limpieza_tokens';
            $this->authService->deleteMatchTokenUser($user->id);

            $stage = 'guardar_match_token';
            $data = [
                'usuario' => $user->id,
                'device' => $deviceInfo->id,
                'token' => $encToken
            ];
            $savematch = $this->authService->createMatchToken($data);
            if (!$savematch) {
                return $this->sendResponse(null, 'Error al crear login:1', 500);
            }


            $data = [
                'token' => $encToken,
                'uname' => $user->nombre,
                'urol' => $rol->descripcion,
                'stockname' => $stock->descripcion,
                'stockid' => $stock->id,
                'deviceid' => $deviceInfo->id,
                'spermisos' => $permisos,
            ];

            return $this->sendResponse($data, 'login success');
        } catch (Throwable $e) {
            Log::error('Error en login', [
                'ref' => $errorRef,
                'stage' => $stage,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return $this->sendResponse(null, "Error interno en login. Ref: {$errorRef}", 500);
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
        } catch (Throwable $e) {
            Log::error('Error en logout', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return $this->sendResponse(false, 'logout failed', 500);
        }
    }
}
