<?php

namespace App\Http\Requests\Farma\Productos;

use App\Http\Requests\Util\FilterRequest;

class FamiliaFilterRequest extends FilterRequest
{
    protected array $allowedOperators = [
        'descripcion' => ['LIKE_ALL', 'AND'],
    ];
}
