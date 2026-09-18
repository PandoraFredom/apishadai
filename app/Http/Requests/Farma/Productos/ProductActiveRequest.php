<?php

namespace App\Http\Requests\Farma\Productos;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ProductActiveRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $this->merge(['id' => $this->route('id')]);
        }
    }

    public function rules(): array
    {
        $updating = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $id = $updating ? (int) $this->route('id') : null;

        return [
            ...($updating ? ['id' => ['required', 'integer', 'exists:prod_activo,id']] : []),
            'producto' => ['required', 'integer', 'exists:productos,id'],
            'pactivo' => [
                'required',
                'integer',
                'exists:principal_activos,id',
                Rule::unique('prod_activo', 'pactivo')
                    ->where(fn ($query) => $query->where('producto', $this->integer('producto')))
                    ->ignore($id),
            ],
        ];
    }

    protected function customMessages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.exists' => 'El valor seleccionado para :attribute no existe.',
            '*.unique' => 'El principio activo ya está asociado con este producto.',
        ];
    }
}
