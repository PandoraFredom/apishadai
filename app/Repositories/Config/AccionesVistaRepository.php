<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\AccionesVistaService;
use App\Models\ActionsVistas;
use App\Repositories\Repository;
use Illuminate\Database\Eloquent\Collection;

class AccionesVistaRepository extends Repository implements AccionesVistaService
{
    public function __construct(ActionsVistas $model)
    {
        parent::__construct($model);
        $this->defaultRelations = ['vista'];
    }

    public function findByVista(int $vistaId): Collection
    {
        return $this->whereList(['vista' => $vistaId]);
    }
}
