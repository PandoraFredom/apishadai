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
        private VistaEstadosService $vistaEstadosService,
        private ModulosRepositoryInterface $modulosService,
        private AccionesVistaService $accionesVistaService
    ) {
        parent::__construct($model);
        $this->defaultRelations = ['estado', 'modulo'];
    }

    public function exist_samenameWhithModuleId($name, $module)
    {
        return $this->whereFirst(['nombre' => $name, 'modulo' => $module]);
    }

    public function findbyModule($moduleId)
    {
        return $this->whereList(['modulo' => $moduleId]);
    }

    public function findByModuloId($moduloId)
    {
        return $this->whereList(['modulo' => $moduloId]);
    }

    public function estadosList()
    {
        return $this->vistaEstadosService->getAll();
    }

    public function modulosList()
    {
        return $this->modulosService->getAll();
    }

    public function acctionList($vistaId)
    {
        return $this->accionesVistaService->findByVista((int) $vistaId);
    }

    public function deleteAccion($id)
    {
        return $this->accionesVistaService->delete((int) $id);
    }

    public function createAccion(array $data)
    {
        return $this->accionesVistaService->create($data);
    }
}
