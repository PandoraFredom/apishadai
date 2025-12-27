<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\AccionesVistaService;
use App\Interfaces\Config\ModulosRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use App\Interfaces\Config\PermisoService;
use App\Interfaces\Config\TipoTiempoService;
use App\Interfaces\Config\VistaRepositoryInterface;
use App\Models\Permisos;
use App\Repositories\Repository;

class PermisoRepository extends Repository implements PermisoService
{
    public function __construct(
        Permisos $model,
        private ModulosRepositoryInterface $moduloService,
        private VistaRepositoryInterface $vistaService,
        private AccionesVistaService $accionesVistaService,
        private TipoTiempoService $tipoTiempoService
    ) {
        parent::__construct($model);
        $this->defaultRelations = ['modulo', 'vista', 'actionvista'];
    }
    public function listByUserId(int $userId): Collection
    {
        return $this->whereList(['usuario' => $userId]);
    }

    public function get_ModuloListCbx(): Collection
    {
        return $this->moduloService->getAll();
    }
    public function get_VistasByModulo(int $moduloId): Collection
    {
        return  $this->vistaService->findByModuloId($moduloId);
    }
    public function get_AccionesByVista(int $vistaId): Collection
    {
        return $this->accionesVistaService->findByVista($vistaId);
    }
    public function tiposTiempoList(): Collection
    {
        return $this->tipoTiempoService->getAll();
    }
}
