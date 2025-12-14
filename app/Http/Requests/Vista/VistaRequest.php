<?php

namespace App\Http\Requests\Vista;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class VistaRequest extends FormRequest
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
            'modulo.id' => 'required|integer|exists:modulos,id',
            'nombre' => 'required|string',
            'codigo' => 'required|string|unique:vistas,codigo',
            'estado.id' => 'required|integer|exists:vista_estados,id',
        ];
    }

    public function messages(): array
    {
        return [
            'modulo.id.required' => 'El módulo es obligatorio',
            'modulo.id.integer' => 'El módulo debe ser un entero',
            'modulo.id.exists' => 'El módulo no existe',

            'nombre.required' => 'El nombre es obligatorio',
            'nombre.string' => 'El nombre debe ser una cadena de texto',

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