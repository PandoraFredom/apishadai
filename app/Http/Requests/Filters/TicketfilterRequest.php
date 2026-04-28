<?php

namespace App\Http\Requests\Filters;

use App\Http\Requests\Util\FilterRequest;

class TicketfilterRequest extends FilterRequest
{

    protected $allowedKeys = ['promocion', 'cliente', 'usuario', 'stock'];

    protected $allowedOperators = ['='];
}
