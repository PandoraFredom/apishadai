<?php

namespace App\Http\Requests\Promos;

use App\Http\Requests\Util\FilterRequest;

class PromoFilterRequest extends FilterRequest
{
    protected array $allowedKeys = [
        'nombre',
        'descripcion',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'valor',
        'impresiones',
    ];

    protected array $allowedOperators = [
        '=',
        '!=',
        'LIKE',
        'LIKE_LEFT',
        'LIKE_RIGHT',
        'LIKE_ALL',
        'IN',
        '>',
        '<',
        '>=',
        '<=',
        'BETWEEN',
    ];
}
