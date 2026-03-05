<?php

namespace App\Interfaces\Auth;

use App\Models\Device;
use App\Models\MatchTokens;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

interface AuthService
{
    /**
     * Obtiene la información del dispositivo del header de la solicitud
     *
     * @param Request $request
     * @return Device|null
     */
    public function getDeviceInfo(Request $request): ?Device;

    /**
     * Obtiene los permisos de un usuario agrupados por módulo > vista > acciones
     *
     * @param int $userId
     * @return Collection
     */
    public function getPermisosUser(int $userId): Collection;

    /**
     * Elimina todos los tokens de coincidencia de un usuario
     *
     * @param int $userId
     * @return void
     */
    public function deleteMatchTokenUser(int $userId): void;

    /**
     * Elimina un token de coincidencia por su valor
     *
     * @param string $token
     * @return bool
     */
    public function deleteMatchTokenByToken(string $token): bool;

    /**
     * Crea un nuevo registro de MatchToken para el usuario
     *
     * @param array $data ['usuario' => int, 'device' => int, 'token' => string]
     * @return MatchTokens|null
     */
    public function createMatchToken(array $data): ?MatchTokens;

    /**
     * Obtiene el token desencriptado del header de autorización
     *
     * @param Request $request
     * @return string|null
     */
    public function getDecryptedToken(Request $request): ?string;

    /**
     * Encripta un token JWT
     *
     * @param string $token
     * @return string
     */
    public function encryptToken(string $token): string;

    /**
     * Genera un hash hmac-sha256 de un valor
     *
     * @param string $value
     * @return string
     */
    public function hashValue(string $value): string;
}
