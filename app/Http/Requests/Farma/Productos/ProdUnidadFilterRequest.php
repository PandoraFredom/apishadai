<?php

namespace App\Http\Requests\Farma\Productos;

use App\Http\Requests\Util\FilterRequest;

class ProdUnidadFilterRequest extends FilterRequest
{
    protected array $allowedOperators = [
        'abreviatura_c' => ['LIKE_ALL', 'AND'],
        'abreviatura_v' => ['LIKE_ALL', 'OR'],
    ];
}
