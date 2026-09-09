<?php

namespace App\Http\Requests\Reportes;

use App\Http\Requests\Util\FilterRequest;

class SorteosFilterRequest extends FilterRequest
{
    protected array $allowedOperators = [
        'nombre' => ['LIKE_ALL', 'AND'],
    ];
}
