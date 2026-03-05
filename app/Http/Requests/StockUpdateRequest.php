<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:stocks,id',
            'descripcion' => [
                'required',
                'string',
                'max:180',
                Rule::unique('stocks', 'descripcion')->ignore($this->input('id')),
            ],
            'telefono' => 'required|string|max:20',
            'ubicacion' => 'required|string|max:180',
            'estado.id' => 'required|integer|exists:stock_estado,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'El id del stock es requerido',
            'id.integer' => 'El id del stock debe ser un número entero',
            'id.exists' => 'El stock seleccionado no existe',
            'descripcion.required' => 'La descripcion es requerida',
            'descripcion.string' => 'La descripcion debe ser una cadena de texto',
            'descripcion.unique' => 'La descripcion ya existe',
            'telefono.required' => 'El telefono es requerido',
            'telefono.string' => 'El telefono debe ser una cadena de texto',
            'telefono.max' => 'El telefono no debe exceder 20 caracteres',
            'ubicacion.required' => 'La ubicacion es requerida',
            'ubicacion.string' => 'La ubicacion debe ser una cadena de texto',
            'estado.id.required' => 'El estado es requerido',
            'estado.id.integer' => 'El estado debe ser un número entero',
            'estado.id.exists' => 'El estado seleccionado no existe',
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
