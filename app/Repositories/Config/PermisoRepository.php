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
use Illuminate\Support\Facades\DB;

class PermisoRepository extends Repository implements PermisoService
{
    public function __construct(
        Permisos $model,
        private readonly ModulosRepositoryInterface $moduloService,
        private readonly VistaRepositoryInterface $vistaService,
        private readonly AccionesVistaService $accionesVistaService,
        private readonly TipoTiempoService $tipoTiempoService
    ) {
        parent::__construct($model);
        $this->defaultRelations = ['modulo', 'vista', 'actionvista'];
    }
    public function listByUserId(int $userId)
    {
        return $this->whereList(['usuario' => $userId], true);
    }

    public function getPermisosByUserId(int $userId): Collection
    {
        return $this->whereList(['usuario' => $userId], false);
    }

    public function get_ModuloListCbx()
    {
        return $this->moduloService->getAll();
    }
    public function get_VistasByModulo(int $moduloId)
    {
        return  $this->vistaService->findByModuloId($moduloId);
    }
    public function get_AccionesByVista(int $vistaId)
    {
        return $this->accionesVistaService->findByVista($vistaId);
    }
    public function tiposTiempoList()
    {
        return $this->tipoTiempoService->getAll();
    }

    public function createUnique(array $data): bool
    {
        return DB::transaction(function () use ($data): bool {
            $conditions = [
                'usuario' => $data['usuario'],
                'modulo' => $data['modulo'],
                'vista' => $data['vista'],
                'actionvista' => $data['actionvista'],
            ];

            $exists = $this->model->newQuery()
                ->where($conditions)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                return false;
            }

            return (bool) $this->model->newQuery()->create($data);
        }, 3);
    }
}
