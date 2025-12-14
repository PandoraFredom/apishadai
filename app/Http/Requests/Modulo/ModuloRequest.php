<?php

namespace App\Http\Requests\Modulo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class ModuloRequest extends FormRequest
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
            'nombre' => 'required|string|unique:modulos,nombre',
            'codigo' => 'required|string|unique:modulos,codigo',
            'estado.id' => 'required|integer|exists:modulo_estados,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.string' => 'El nombre debe ser una cadena de texto',
            'nombre.unique' => 'El nombre ya está en uso',

            'codigo.required' => 'El código es obligatorio',
            'codigo.string' => 'El código debe ser una cadena de texto',
            'codigo.unique' => 'El código ya está en uso',

            'estado.id.required' => 'El estado es obligatorio',
            'estado.id.integer' => 'El estado debe ser un entero',
            'estado.id.exists' => 'El estado no existe',
        ];
    }
    /**
     * Summary of failedValidation
     * @param Validator $validator
     * @return \Illuminate\Http\JsonResponse
     */
    protected function failedValidation(Validator $validator)
    {
        $response = [
            'message' => $validator->errors()->first(),
            'code' => 422,
            'data' => false,
        ];

        return response()->json($response, 422);
    }
}
