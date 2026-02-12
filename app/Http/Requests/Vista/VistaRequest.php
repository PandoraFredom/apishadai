<?php

namespace App\Http\Requests\Vista;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

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
            'nombre' => 'required|string|max:50|unique:vistas,nombre',
            'codigo' => 'required|string|max:10|unique:vistas,codigo',
            'estado.id' => 'required|integer|exists:vista_estados,id',
        ];
    }

    public function messages(): array
    {
        return [
            'modulo.id.required' => 'El campo modulo es obligatorio.',
            'modulo.id.integer' => 'El campo modulo debe ser un número entero.',
            'modulo.id.exists' => 'El modulo seleccionado no existe.',

            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 50 caracteres.',
            'nombre.unique' => 'Ya existe una vista con ese nombre.',

            'codigo.required' => 'El campo codigo es obligatorio.',
            'codigo.string' => 'El campo codigo debe ser una cadena de texto.',
            'codigo.max' => 'El campo codigo no debe exceder los 10 caracteres.',
            'codigo.unique' => 'Ya existe una vista con ese codigo.',

            'estado.id.required' => 'El campo estado es obligatorio.',
            'estado.id.integer' => 'El campo estado debe ser un número entero.',
            'estado.id.exists' => 'El estado seleccionado no existe.',

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
