<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\MatchTokensService;
use App\Models\MatchTokens;
use App\Repositories\Repository;
use Illuminate\Database\Eloquent\Collection;

class MatchTokensRepository extends Repository implements MatchTokensService
{
    public function __construct(MatchTokens $model)
    {
        parent::__construct($model);
        $this->perPage = 30;
    }

    /**
     * Obtiene todos los tokens de un usuario
     *
     * @param int $userId
     * @return Collection
     */
    public function getByUserId(int $userId): Collection
    {
        return $this->whereList(['usuario' => $userId]);
    }

    /**
     * Obtiene un token por su identificador
     *
     * @param string $token
     * @return MatchTokens|null
     */
    public function getByToken(string $token): ?MatchTokens
    {
        return $this->whereFirst(['token' => $token]);
    }

    /**
     * Elimina todos los tokens de un usuario
     *
     * @param int $userId
     * @return bool
     */
    public function deleteByUserId(int $userId): bool
    {
        return MatchTokens::where('usuario', $userId)->delete() > 0;
    }
}
