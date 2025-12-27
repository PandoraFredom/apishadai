<?php

namespace App\Interfaces\Config;

use App\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface PermisoService extends RepositoryInterface
{

    public function listByUserId(int $userId): Collection;
    public function get_ModuloListCbx(): Collection;
    public function get_VistasByModulo(int $moduloId): Collection;
    public function get_AccionesByVista(int $vistaId): Collection;
    public function tiposTiempoList(): Collection;
}
