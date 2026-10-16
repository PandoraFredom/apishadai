<?php

namespace App\Http\Requests\Pos;

use App\Http\Requests\BaseRequest;

class ClienteposListRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1'], 'search' => ['nullable', 'string', 'max:180']];
    }
}
