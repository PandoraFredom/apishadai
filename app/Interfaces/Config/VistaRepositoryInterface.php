<?php
namespace App\Interfaces\Config;

use App\Interfaces\RepositoryInterface;
use App\Models\Vistas;
use Illuminate\Database\Eloquent\Collection;

interface VistaRepositoryInterface extends RepositoryInterface
{
    public function exist_samenameWhithModuleId($name, $module): Vistas|null;
    public function findbyModule($moduleId): Vistas|null;
    public function findByModuloId($moduloId): Collection|null;
    public function estadosList(): Collection;
    public function modulosList(): Collection;

    public function acctionList($vistaId): Collection;
    public function deleteAccion($id): bool;
    public function createAccion(array $data): bool;
}
