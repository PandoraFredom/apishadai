<?php

namespace App\Http\Requests\Filters;

use App\Http\Requests\Util\FilterRequest;

class ClientesFilterRequest extends FilterRequest
{
    protected array $allowedOperators = [
        'docid' => ['LIKE_ALL', 'AND'],
        'pnombre' => ['LIKE_ALL', 'OR'],
    ];
}
