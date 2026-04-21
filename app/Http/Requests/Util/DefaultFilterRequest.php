<?php

namespace App\Http\Requests\Util;

class DefaultFilterRequest extends FilterRequest
{
    protected array $allowedKeys = [
        'docid',
        'pnombre',
        'papellido',
    ];

    protected array $allowedOperators = [
        '=',
        '!=',
        'LIKE',
        'LIKE_LEFT',
        'LIKE_RIGHT',
        'LIKE_ALL',
        'IN',
        '>',
        '<',
        '>=',
        '<=',
        'BETWEEN',
    ];
}
