<?php

namespace App\Interfaces\Config;

use App\Interfaces\RepositoryInterface;

interface MatchTokensService extends RepositoryInterface
{
    /**
     * Obtiene todos los tokens de un usuario
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUserId(int $userId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Obtiene un token por su identificador
     *
     * @param string $token
     * @return \App\Models\MatchTokens|null
     */
    public function getByToken(string $token): ?\App\Models\MatchTokens;

    /**
     * Elimina todos los tokens de un usuario
     *
     * @param int $userId
     * @return bool
     */
    public function deleteByUserId(int $userId): bool;
}
