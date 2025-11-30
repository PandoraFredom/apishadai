<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\MatchTokens;
use App\Models\Permisos;
use App\Models\User;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
  

    public function __construct(private EncryptionService $encService)
    {
       
    }


    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'password' => 'required|string|max:100',
            ]);

            if ($validator->fails()) {
                return $this->sendResponse(null, "login failed:" . $validator->errors()->first(), 400);
            }

            $credentials = $request->only('name', 'password');

            if (!$token = auth()->attempt($credentials)) {
                return $this->sendResponse(null, 'Credenciales incorrectas', 401);
            }

            $deviceInfo = $this->getDeviceInfo($request);

            if (!$deviceInfo) {
                $ip_shash = $this->encService->genHash($request['ip']);
                return $this->sendResponse(null, "Error al obtener la informacion del dispositivo:$ip_shash", 401);// remover en produccion 
            }

            $encToken = $this->encService->encrypt($token);
            if ($deviceInfo !== null) {
                $this->deleteMatchTokenUser(auth()->user()->id);

                $data = [
                    'usuario' => auth()->user()->id,
                    'device' => $deviceInfo->id,
                    'token' => $encToken
                ];
                $savematch = MatchTokens::create($data);
                if (!$savematch) {
                    return $this->sendResponse(null, 'Error al crear login:1', 500);
                }
                $permisos = $this->getPermisosUser(auth()->user()->id);


                if ($permisos->isEmpty()) {
                    return $this->sendResponse(null, 'No tiene permisos asignados:' . auth()->user()->id, 403);
                }
                $data = [
                    'token' => $encToken,
                    'uname' => auth()->user()->nombre,
                    'urol' => auth()->user()->Rol->descripcion,
                    'stockname' => $deviceInfo->Stock->descripcion,
                    'stockid' => $deviceInfo->Stock->id,
                    'deviceid' => $deviceInfo->id,
                    'spermisos' => $permisos,
                ];

                return $this->sendResponse($data, 'login success');
            }
            return $this->sendResponse(null, 'Error al crear login:2:', 500);

        } catch (\Exception $e) {
            return $this->sendResponse(null, 'Error al crear login:3::' . $e->getMessage(), 500);
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|unique:users,nombre',
            'rol.id' => 'required|integer|exists:roles,id',
            'name' => 'required|string|min:5',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:5',
            'estado.id' => 'required|integer|exists:user_estados,id',
        ]);

        $input = $request->all();

        if ($validator->fails()) {
            return $this->sendResponse(false, $validator->errors()->first() . $input['password'], 500);
        }

        $input['password'] = bcrypt($input['password']);
        $input['name'] = bcrypt($input['name']);
        $input['rol'] = $input['rol']['id'];
        $input['estado'] = $input['estado']['id'];
        $user = User::create($input);
        if ($user) {
            return $this->sendResponse(true, 'register success');
        }
        return $this->sendResponse(false, 'register failed', 500);
    }

    public function update(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'id' => "required|integer|exists:users,id",
                'rol.id' => "required|integer|exists:roles,id",
                'estado.id' => "required|integer|exists:user_estados,id",
            ]);

            if ($validate->fails()) {
                return $this->sendResponse(false, $validate->errors()->first(), 500);
            }
            $id = $request->id;
            $input = [
                'rol' => $request->rol['id'],
                'estado' => $request->estado['id'],
            ];

            $update = User::find($id)->update($input);
            if ($update) {
                return $this->sendResponse(true, 'update success');
            }
            return $this->sendResponse(false, 'update failed', 500);
        } catch (\Exception $e) {
            return $this->sendResponse(false, "update failed: {$e->getMessage()}", 500);
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

                    auth()->logout();

                    return $this->sendResponse(true, 'logout success');
                }
            }
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'logout failed', 500);
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
        }
        return $device;
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
