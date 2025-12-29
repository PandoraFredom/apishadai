<?php
namespace App\Interfaces\Config;

use App\Interfaces\RepositoryInterface;

interface VistaRepositoryInterface extends RepositoryInterface
{
    public function exist_samenameWhithModuleId($name, $module);
    public function findbyModule($moduleId);
    public function findByModuloId($moduloId);
    public function estadosList();
    public function modulosList();

    public function acctionList($vistaId);
    public function deleteAccion($id);
    public function createAccion(array $data);
}
