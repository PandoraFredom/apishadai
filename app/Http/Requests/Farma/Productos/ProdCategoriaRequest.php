<?php

namespace App\Http\Requests\Farma\Productos;

use App\DTO\Farma\ProdCategoriaData;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ProdCategoriaRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $this->merge(['id' => $this->route('id')]);
        }

        $this->merge(ProdCategoriaData::sanitize($this->all()));
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
            ...($updating ? ['id' => ['required', 'integer', 'exists:prod_categorias,id']] : []),
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('prod_categorias', 'nombre')->ignore($id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.unique' => 'El valor de :attribute ya existe.',
            '*.exists' => 'El valor seleccionado para :attribute no existe.',
            '*.max' => 'El campo :attribute excede la longitud permitida.',
            '*.integer' => 'El campo :attribute debe ser un número entero.',
            '*.min' => 'El campo :attribute debe ser mayor que cero.',
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
