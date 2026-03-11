<?php

namespace App\Repositories\Auth;

use App\Interfaces\Auth\AuthService;
use App\Interfaces\Config\MatchTokensService;
use App\Interfaces\Config\PermisoService;
use App\Models\MatchTokens;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AuthRepository implements AuthService
{
    public function __construct(
        private EncryptionService $encService,
        private PermisoService $permisoService,
        private MatchTokensService $matchTokensService
    ) {}



    /**
     * Obtiene los permisos de un usuario agrupados por módulo > vista > acciones
     *
     * @param int $userId
     * @return Collection
     */
    public function getPermisosUser(int $userId): Collection
    {
        $permisos = $this->permisoService->listByUserId($userId);

        return $permisos
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

    /**
     * Elimina todos los tokens de coincidencia de un usuario
     *
     * @param int $userId
     * @return void
     */
    public function deleteMatchTokenUser(int $userId): void
    {
        $this->matchTokensService->deleteByUserId($userId);
    }

    /**
     * Elimina un token de coincidencia por su valor
     *
     * @param string $token
     * @return bool
     */
    public function deleteMatchTokenByToken(string $token): bool
    {
        $matchToken = $this->matchTokensService->getByToken($token);

        if ($matchToken) {
            return $this->matchTokensService->delete($matchToken->id);
        }

        return false;
    }

    /**
     * Crea un nuevo registro de MatchToken para el usuario
     *
     * @param array $data ['usuario' => int, 'device' => int, 'token' => string]
     * @return MatchTokens|null
     */
    public function createMatchToken(array $data): ?MatchTokens
    {
        $success = $this->matchTokensService->create($data);

        if ($success) {
            return $this->matchTokensService->whereFirst([
                ['usuario', '=', $data['usuario']],
                ['device', '=', $data['device']],
                ['token', '=', $data['token']]
            ]);
        }

        return null;
    }

    /**
     * Obtiene el token desencriptado del header de autorización
     *
     * @param Request $request
     * @return string|null
     */
    public function getDecryptedToken(Request $request): ?string
    {
        if ($request->hasHeader('Authorization')) {
            $header = $request->header('Authorization');

            if (str_starts_with($header, 'Bearer ')) {
                $token = substr($header, 7);
                return $this->encService->decrypt($token);
            }
        }
        return null;
    }

    /**
     * Encripta un token JWT
     *
     * @param string $token
     * @return string
     */
    public function encryptToken(string $token): string
    {
        return $this->encService->encrypt($token);
    }

    /**
     * Genera un hash hmac-sha256 de un valor
     *
     * @param string $value
     * @return string
     */
    public function hashValue(string $value): string
    {
        return $this->encService->genHash($value);
    }
}

