<?php

namespace App\Http\Requests\Filters;

use App\Http\Requests\Util\FilterRequest;

class StocksFilterRequest extends FilterRequest
{
    protected array $allowedOperators = [
        'descripcion' => ['LIKE_ALL', 'AND'],
    ];
}
