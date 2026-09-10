<?php

namespace App\Http\Requests\Farma\Productos;

use App\DTO\Farma\ProdUnidadData;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ProdUnidadRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $this->merge(['id' => $this->route('id')]);
        }

        $this->merge(ProdUnidadData::sanitize($this->all()));
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
            ...($updating ? ['id' => ['required', 'integer', 'exists:prod_unidades,id']] : []),
            'abreviatura_c' => ['required', 'string', 'max:10'],
            'abreviatura_v' => ['required', 'string', 'max:10'],
            'cantidad_c' => ['required', 'integer', 'min:1'],
            'cantidad_v' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('prod_unidades', 'cantidad_v')
                    ->where(fn ($query) => $query
                        ->where('abreviatura_c', trim((string) ($this->input('abreviatura_c', ''))))
                        ->where('abreviatura_v', trim((string) ($this->input('abreviatura_v', ''))))
                        ->where('cantidad_c', $this->integer('cantidad_c')))
                    ->ignore($id),
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
