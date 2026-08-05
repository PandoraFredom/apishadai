<?php

namespace App\Http\Requests\Farma\Productos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductActiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $updating = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $id = $updating ? $this->integer('id') : null;

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

    public function messages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.exists' => 'El valor seleccionado para :attribute no existe.',
            '*.unique' => 'El principio activo ya está asociado con este producto.',
        ];
    }
}
