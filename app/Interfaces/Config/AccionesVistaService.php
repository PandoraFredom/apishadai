<?php

namespace App\Interfaces\Config;

use App\Interfaces\RepositoryInterface;


interface AccionesVistaService extends RepositoryInterface
{
    public function findByVista(int $vistaId);
    public function existCodigoEnVista(int $vista, string $codigo): bool;
    public function existNombreEnVista(int $vista, string $nombre): bool;
}
