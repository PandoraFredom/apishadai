<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class StockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'descripcion' => 'required|string|max:180|unique:stocks,descripcion',
            'telefono' => 'required|string|max:8',
            'ubicacion' => 'required|string|max:180',
        ];
    }

    public function messages(): array
    {
        return [
            'descripcion.required' => 'La descripcion es requerida',
            'descripcion.string' => 'La descripcion debe ser una cadena de texto',
            'descripcion.unique' => 'La descripcion ya existe',
            'telefono.required' => 'El telefono es requerido',
            'telefono.string' => 'El telefono debe ser una cadena de texto',
            'ubicacion.required' => 'La ubicacion es requerida',
            'ubicacion.string' => 'La ubicacion debe ser una cadena de texto',
        ];
    }



    /**
     * Summary of failedValidation
     * @param Validator $validator
     * @throws ValidationException
     */
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
