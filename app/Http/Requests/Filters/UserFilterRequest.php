<?php

namespace App\Http\Requests\Filters;

use App\Http\Requests\Util\FilterRequest;

class UserFilterRequest extends FilterRequest
{

    protected array $allowedKeys = ['nombre'];


    protected array $allowedOperators = ['='];
}
