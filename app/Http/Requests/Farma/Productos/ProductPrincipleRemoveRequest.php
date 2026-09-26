<?php

namespace App\Http\Requests\Farma\Productos;

use App\Http\Requests\BaseRequest;

class ProductPrincipleRemoveRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['pactivo' => ['required', 'integer', 'min:1']];
    }
}
