<?php

namespace App\Http\Requests\Modulo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class ModuloUpdateRequest extends FormRequest
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
            'estado.id' => 'required|integer|exists:modulo_estados,id',
        ];
    }
    public function messages(): array
    {
        return [
            'estado.id.required' => 'El campo estado.id es obligatorio.',
            'estado.id.integer' => 'El campo estado.id debe ser un número entero.',
            'estado.id.exists' => 'El estado.id proporcionado no existe en la base de datos.',
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
