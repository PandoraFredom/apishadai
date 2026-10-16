<?php

namespace App\Http\Requests\Farma\Transferencias;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class TransferTypeRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('descripcion'))) {
            $this->merge(['descripcion' => trim(strip_tags($this->input('descripcion')))]);
        }
    }

    public function rules(): array
    {
        return [
            'id' => [$this->isMethod('PUT') ? 'required' : 'prohibited', 'integer', 'min:1'],
            'descripcion' => ['required', 'string', 'max:255',
                Rule::unique('transferencias_tipo', 'descripcion')->ignore($this->isMethod('PUT') ? $this->integer('id') : null)],
        ];
    }
}
