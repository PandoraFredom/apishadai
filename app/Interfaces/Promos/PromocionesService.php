<?php

namespace App\Interfaces\Promos;

use App\Http\Requests\Util\FilterRequest;
use App\Interfaces\RepositoryInterface;

interface PromocionesService extends RepositoryInterface
{
    public function get_estadosList();
    public function get_promoActive();
    public function filterPromos(FilterRequest $request);
}
