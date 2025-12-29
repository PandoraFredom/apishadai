<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\AccionesVistaService;
use App\Interfaces\Config\ModulosRepositoryInterface;
use App\Interfaces\Config\VistaEstadosService;
use App\Interfaces\Config\VistaRepositoryInterface;
use App\Models\Vistas;
use App\Repositories\Repository;

class VistaRepository extends Repository implements VistaRepositoryInterface
{
    public function __construct(
        Vistas $model,
        private VistaEstadosService $estadoRepository,
        private ModulosRepositoryInterface $moduloRepository,
        private AccionesVistaService $accionesVistaRepository
    ) {
        parent::__construct($model);
        $this->defaultRelations = ['modulo', 'estado'];
        $this->perPage = 15;
        $this->orderBy = ["id", "desc"];
    }

    public function exist_samenameWhithModuleId($name, $module)
    {
        return $this->whereFirst([
            ['nombre', '=', $name],
            ['modulo', '=', $module],
        ]);
    }

    public function findbyModule($moduleId)
    {
        return $this->whereFirst([
            ['modulo', '=', $moduleId],
        ]);
    }

    public function findByModuloId($moduloId)
    {
        return $this->whereList([
            ['modulo', '=', $moduloId],
        ]);
    }
    public function estadosList()
    {
        return $this->estadoRepository->getAll();
    }
    public function modulosList()
    {
        return $this->moduloRepository->getAll();
    }

    public function acctionList($vistaId)
    {
        return $this->accionesVistaRepository->findByVista($vistaId);
    }
    public function createAccion(array $data): bool
    {
        return $this->accionesVistaRepository->create($data);
    }
    public function deleteAccion($id): bool
    {
        return $this->accionesVistaRepository->delete($id);
    }
}
