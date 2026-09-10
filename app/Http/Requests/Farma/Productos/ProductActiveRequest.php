<?php

namespace App\Http\Requests\Farma\Productos;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ProductActiveRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $this->merge(['id' => $this->route('id')]);
        }
    }

    public function authorize(): bool
    {
        return true;
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

    public function messages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.exists' => 'El valor seleccionado para :attribute no existe.',
            '*.unique' => 'El principio activo ya está asociado con este producto.',
        ];
    }

    /** @throws HttpResponseException */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
            'code' => 400,
            'data' => null,
        ], 400));
    }
}
