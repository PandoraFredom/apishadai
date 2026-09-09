<?php

namespace App\Http\Requests\Promos;

use App\Http\Requests\Util\FilterRequest;

class PromoFilterRequest extends FilterRequest
{
    protected array $allowedOperators = [
        'nombre' => ['LIKE_ALL', 'AND'],
        'descripcion' => ['LIKE_ALL', 'AND'],
    ];
}
