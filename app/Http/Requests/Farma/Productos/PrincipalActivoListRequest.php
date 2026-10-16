<?php

namespace App\Http\Requests\Farma\Productos;

use App\Http\Requests\BaseRequest;

class PrincipalActivoListRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'all' => ['sometimes', 'boolean'],
        ];
    }
}
