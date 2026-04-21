<?php

namespace App\Interfaces\Promos;

use App\Http\Requests\Util\FilterRequest;
use App\Interfaces\RepositoryInterface;

interface PromocionesService extends RepositoryInterface
{
    public function get_estadosList();
    public function get_promoActive();
    public function another_active_promo(int $current_promo_id): bool;
    public function filterPromos(FilterRequest $request);
}
