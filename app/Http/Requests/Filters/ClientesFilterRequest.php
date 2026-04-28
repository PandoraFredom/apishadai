<?php


namespace App\Http\Requests\Filters;

use App\Http\Requests\Util\FilterRequest;

class ClientesFilterRequest extends FilterRequest
{
    protected array $allowedKeys = ['docid', 'pnombre', 'telefono'];


    protected array $allowedOperators = ['=', 'LIKE'];
}
