<?php

namespace App\Http\Requests\Filters;

use App\Http\Requests\Util\FilterRequest;

class TicketfilterRequest extends FilterRequest
{
    protected array $allowedOperators = [
        'promocion' => ['=', 'AND'],
        'cliente' => ['=', 'AND'],
        'usuario' => ['=', 'AND'],
        'stock' => ['=', 'AND'],
        'ntiket' => ['LIKE_ALL', 'AND'],
    ];
}
