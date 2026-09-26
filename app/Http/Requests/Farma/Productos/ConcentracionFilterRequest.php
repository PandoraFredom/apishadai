<?php

namespace App\Http\Requests\Farma\Productos;

use App\Http\Requests\Util\FilterRequest;

class ConcentracionFilterRequest extends FilterRequest
{
    protected array $allowedOperators = [
        'valor' => ['LIKE_ALL', 'AND'],
    ];
}
