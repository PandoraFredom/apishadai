<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\RolesRepositoryInterface;
use App\Interfaces\Config\UserEstadoRepositoryInterface;
use App\Interfaces\Config\UserRepositoryInterface;
use App\Models\User;
use App\Repositories\Repository;

class UserRepository extends Repository implements UserRepositoryInterface
{
    public function __construct(
        User $model,
        private UserEstadoRepositoryInterface $estadoService,
        private RolesRepositoryInterface $rolesService
    ) {
        parent::__construct($model);
        $this->defaultRelations = ['rol', 'estado'];
    }

    public function get_estadoList()
    {
        return $this->estadoService->getAll();
    }
    public function get_rolList()
    {
        return $this->rolesService->getAll();
    }

    public function assign_Permiso(int $userId, array $permisos): bool
    {
        return false;
    }
    public function get_Permisos(int $userId): array
    {
        return [];
    }
    public function remove_Permiso(int $userId, array $permisos): bool
    {
        return false;
    }
}
