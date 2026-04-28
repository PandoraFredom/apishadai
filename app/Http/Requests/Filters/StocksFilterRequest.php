<?php


namespace App\Http\Requests\Filters;

use App\Http\Requests\Util\FilterRequest;


class StocksFilterRequest extends FilterRequest
{
    protected array $allowedKeys = ['descripcion', 'ubicacion'];

    protected array $allowedOperators = ['='];
}
