<?php

namespace App\Http\Requests\Promos;

use App\Http\Requests\Util\FilterRequest;

class PromoFilterRequest extends FilterRequest
{
    protected array $allowedKeys = [
        'nombre',
        'descripcion',
    ];

    protected array $allowedOperators = [
        '=',
        '!=',
        'LIKE',
    ];
}
