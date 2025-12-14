<?php

namespace App\Repositories\Config;


use App\Interfaces\Config\VistaRepositoryInterface;
use App\Models\ActionsVistas;
use App\Models\Modulos;
use App\Models\VistaEstados;
use App\Models\Vistas;
use App\Repositories\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class VistaRepository extends Repository implements VistaRepositoryInterface
{
    public function __construct(Vistas $vista)
    {
        parent::__construct($vista);
        $this->defaultRelations = ['modulo', 'estado'];
        $this->perPage = 15;
        $this->orderBy = ["id", "desc"];
    }

    public function exist_samenameWhithModuleId($name, $module): Vistas|null
    {
        return Vistas::where('modulo', $module)->where('nombre', $name)->first();
    }

    public function findbyModule($moduleId): Vistas|null
    {
        return Vistas::where('modulo', $moduleId)->first();
    }


    public function findByModuloId($moduloId): Collection|null
    {
        return Vistas::where('modulo', $moduloId)->get();
    }
    public function estadosList(): Collection
    {
        return VistaEstados::all();
    }
    public function modulosList(): Collection
    {
        return Modulos::all();
    }

    public function acctionList($vistaId): Collection
    {
        return ActionsVistas::where('vista', $vistaId)->get();
    }
    public function createAccion(array $data): bool
    {
        try {
            return ActionsVistas::create($data) !== null;
        } catch (\Exception $e) {
            Log::error('VistaRepository::createAccion - ' . $e->getMessage(), [
                'model' => get_class($this->model),
                'exception' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return false;
        }


    }
    public function deleteAccion($id): bool
    {
        try {
            return ActionsVistas::destroy($id) !== null;
        } catch (\Exception $e) {
            Log::error('VistaRepository::deleteAccion - ' . $e->getMessage(), [
                'model' => get_class($this->model),
                'exception' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return false;
        }
    }
}