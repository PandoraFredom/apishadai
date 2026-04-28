<?php

namespace App\Http\Requests\Filters;

use App\Http\Requests\Util\FilterRequest;


class SorteosfilterRequest extends FilterRequest
{
     protected $allowedKeys = ['nombre'];

    protected $allowedOperators = ['=', 'LIKE'];
}

