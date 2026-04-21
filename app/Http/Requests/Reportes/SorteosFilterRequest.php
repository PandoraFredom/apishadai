<?php

namespace App\Http\Requests\Reportes;

use App\Http\Requests\Util\FilterRequest;

class SorteosFilterRequest extends FilterRequest
{
    protected array $allowedKeys = [
        'promocion',
        'cliente',
        'usuario',
        'stock',
    ];

    protected array $allowedOperators = [
        '=',
    ];

}
