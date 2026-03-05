<?php

namespace App\Http\Requests\Modulo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

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
            'id' => 'required|integer|exists:modulos,id',
            'estado.id' => 'required|integer|exists:modulo_estados,id',
        ];
    }
    public function messages(): array
    {
        return [
            'id.required' => 'El campo id es obligatorio.',
            'id.integer' => 'El campo id debe ser un número entero.',
            'id.exists' => 'El id proporcionado no existe en la base de datos.',

            'estado.id.required' => 'El campo estado.id es obligatorio.',
            'estado.id.integer' => 'El campo estado.id debe ser un número entero.',
            'estado.id.exists' => 'El estado.id proporcionado no existe en la base de datos.',
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
