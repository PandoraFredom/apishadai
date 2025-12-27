<?php

namespace App\Interfaces\Config;

use App\Interfaces\RepositoryInterface;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function get_rolList();
    public function get_estadoList();

    public function get_Permisos(int $userId): array;
    public function assign_Permiso(int $userId, array $permisos): bool;
    public function remove_Permiso(int $userId, array $permisos): bool;
}
