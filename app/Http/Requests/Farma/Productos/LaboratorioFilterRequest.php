<?php

namespace App\Http\Requests\Farma\Productos;

use App\Http\Requests\Util\FilterRequest;

class LaboratorioFilterRequest extends FilterRequest
{
    protected array $allowedOperators = [
        'nombre' => ['LIKE_ALL', 'AND'],
    ];
}
