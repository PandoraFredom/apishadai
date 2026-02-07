<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Device;
use App\Models\MatchTokens;
use App\Models\Permisos;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{


    public function __construct(private EncryptionService $encService) {}


    public function login(LoginRequest $request)
    {
        try {

            $deviceInfo = $this->getDeviceInfo($request);

            if ($deviceInfo == null) {
                $ip_shash = $this->encService->genHash($request['name']);
                return $this->sendResponse(null, "Error al obtener la informacion del dispositivo-login:$ip_shash", 401); // remover en produccion
            }
            $credentials = $request->only('name', 'password');

            $credentials['name'] = $this->encService->genHash($credentials['name']);

            if (!$token = Auth::attempt($credentials)) {
                return $this->sendResponse(null, 'Credenciales incorrectas.', 401);
            }



            $encToken = $this->encService->encrypt($token);

            $this->deleteMatchTokenUser(Auth::user()->id);

            $data = [
                'usuario' => Auth::user()->id,
                'device' => $deviceInfo->id,
                'token' => $encToken
            ];
            $savematch = MatchTokens::create($data);
            if (!$savematch) {
                return $this->sendResponse(null, 'Error al crear login:1', 500);
            }
            $permisos = $this->getPermisosUser(Auth::user()->id);


            if ($permisos->isEmpty()) {
                return $this->sendResponse(null, 'No tiene permisos asignados:' . Auth::user()->id, 403);
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
            return $this->sendResponse(null, 'Error al crear login:3::' . $e->getMessage(), 500);
        }
    }





    public function logout(Request $request)
    {
        try {
            if ($request->hasHeader('Authorization')) {

                $header = $request->header('Authorization');

                if (str_starts_with($header, 'Bearer ')) {

                    $token = substr($header, 7);

                    $descryptToken = $this->encService->decrypt($token);

                    JWTAuth::invalidate($descryptToken);

                    MatchTokens::where('token', $token)->delete();

                    Auth::logout();

                    return $this->sendResponse(true, 'logout success');
                }
            }
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'logout failed:' . $e->getMessage(), 500);
        }
    }

    private function getDeviceInfo(Request $request)
    {
        if ($request->hasHeader('X-Device-Ip') && $request->hasHeader('X-Device-Name')) {

            $device = Device::with('estado')
                ->where('ip', $this->encService->genHash($request['ip']))
                ->where('ip2', $this->encService->genHash($request->ip()))
                ->where('name', $this->encService->genHash($request['name']))
                ->first();
            return $device;
        }
        return null;
    }


    private function getPermisosUser($userId)
    {
        return Permisos::with([
            'modulo.estado',
            'vista.estado',
            'actionvista'
        ])
            ->where('usuario', $userId)
            ->get()
            ->filter(fn($Permiso) => optional($Permiso->Modulo->Estado)->descripcion === 'ACTIVO'
                && optional($Permiso->Vista->Estado)->descripcion === 'ACTIVO')
            ->groupBy(fn($Permiso) => $Permiso->Modulo->id)
            ->map(function ($moduloPermisos) {
                $modulo = $moduloPermisos->first()->Modulo;

                return [
                    'modulo_l' => [
                        'codigo' => $modulo->codigo,
                    ],
                    'vistas_l' => $moduloPermisos->groupBy(fn($permiso) => $permiso->Vista->id)->map(function ($vistaPermisos) {
                        $vista = $vistaPermisos->first()->Vista;

                        return [
                            'vistai' => [
                                'codigo' => $vista->codigo,
                            ],
                            'accionesi' => $vistaPermisos->pluck('Actionvista')->filter()->map(fn($accion) => [
                                'codigo' => $accion->codigo,
                            ])->values()
                        ];
                    })->values()
                ];
            })->values();
    }

    private function deleteMatchTokenUser($userId)
    {
        MatchTokens::where('usuario', $userId)->delete();
    }
}
