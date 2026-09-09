<?php

namespace App\Http\Requests\Util;

class DefaultFilterRequest extends FilterRequest
{
    protected array $allowedOperators = [
        'pnombre' => ['LIKE_ALL', 'AND'],
        'papellido' => ['LIKE_ALL', 'AND'],
        'docid' => ['LIKE_ALL', 'OR'],
    ];
}
