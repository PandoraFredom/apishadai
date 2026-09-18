<?php

namespace App\Http\Requests;

class VersionRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'version' => 'required|string',
        ];
    }
}
