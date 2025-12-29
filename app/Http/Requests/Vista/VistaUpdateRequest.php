<?php

namespace App\Http\Requests\Vista;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
class VistaUpdateRequest extends FormRequest
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
            'id' => 'required|integer|exists:vistas,id',
            'estado.id' => 'required|integer|exists:vista_estados,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'El campo id es obligatorio.',
            'id.integer' => 'El campo id debe ser un número entero.',
            'id.exists' => 'La vista especificada no existe.',

            'estado.id.required' => 'El campo estado es obligatorio.',
            'estado.id.integer' => 'El campo estado debe ser un número entero.',
            'estado.id.exists' => 'El estado seleccionado no es válido.',
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
