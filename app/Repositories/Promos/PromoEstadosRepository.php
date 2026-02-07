<?php

namespace App\Repositories\Promos;

use App\Interfaces\Promos\PromoEstadosService;
use App\Models\PromoEstado;
use App\Repositories\Repository;

class PromoEstadosRepository extends Repository implements PromoEstadosService {
    public function __construct(PromoEstado $model) {
        parent::__construct($model);
    }

}
