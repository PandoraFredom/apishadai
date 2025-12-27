<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
class UserUpdateRequest extends FormRequest
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
            'rol.id' => 'required|integer|exists:roles,id',
            'estado.id' => 'required|integer|exists:user_estados,id',
        ];
    }

    public function messages(): array
    {
        return [
            'rol.id.required' => 'El campo rol es obligatorio.',
            'rol.id.integer' => 'El campo rol debe ser un número entero.',
            'rol.id.exists' => 'El rol seleccionado no es válido.',
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
