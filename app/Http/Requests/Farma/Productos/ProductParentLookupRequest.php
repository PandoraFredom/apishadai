<?php

namespace App\Http\Requests\Farma\Productos;

use App\Http\Requests\BaseRequest;

class ProductParentLookupRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'exclude' => ['nullable', 'regex:/^[1-9][0-9]{0,19}$/'],
        ];
    }
}
