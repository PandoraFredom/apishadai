<?php

namespace App\Http\Requests\Filters;

use App\Http\Requests\Util\FilterRequest;

class SorteosfilterRequest extends FilterRequest
{
    protected array $allowedOperators = [
        'nombre' => ['LIKE_ALL', 'AND'],
    ];
}
