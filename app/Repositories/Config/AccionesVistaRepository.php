<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\AccionesVistaService;
use App\Models\ActionsVistas;
use App\Repositories\Repository;

class AccionesVistaRepository extends Repository implements AccionesVistaService
{
    public function __construct(ActionsVistas $model)
    {
        parent::__construct($model);
        $this->defaultRelations = ['vista'];
    }

    public function findByVista(int $vistaId)
    {
        return $this->whereList(['vista' => $vistaId]);
    }

    public function existCodigoEnVista(int $vista, string $codigo): bool
    {
        return $this->whereFirst(['vista' => $vista, 'codigo' => $codigo]) !== null;
    }

    public function existNombreEnVista(int $vista, string $nombre): bool
    {
        return $this->whereFirst(['vista' => $vista, 'nombre' => $nombre]) !== null;
    }
}
