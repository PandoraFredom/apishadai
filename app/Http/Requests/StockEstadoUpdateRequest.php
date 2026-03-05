<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockEstadoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:stock_estado,id',
            'descripcion' => [
                'required',
                'string',
                'max:200',
                Rule::unique('stock_estado', 'descripcion')->ignore($this->input('id')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'El id del estado es requerido',
            'id.integer' => 'El id del estado debe ser un número entero',
            'id.exists' => 'El estado seleccionado no existe',
            'descripcion.required' => 'La descripcion es requerida',
            'descripcion.string' => 'La descripcion debe ser una cadena de texto',
            'descripcion.max' => 'La descripcion no debe exceder 200 caracteres',
            'descripcion.unique' => 'La descripcion ya existe',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = [
            'message' => $validator->errors()->first(),
            'code' => 400,
            'data' => null,
        ];
        http_response_code(400);
        exit(json_encode($response));
    }
}
