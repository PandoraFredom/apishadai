<?php

namespace App\Interfaces\Config;

use App\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface PermisoService extends RepositoryInterface
{

    public function listByUserId(int $userId);
    public function get_ModuloListCbx();
    public function get_VistasByModulo(int $moduloId);
    public function get_AccionesByVista(int $vistaId);
    public function tiposTiempoList();
}
