<?php

namespace App\Http\Requests\Permiso;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class PermisoRequest extends FormRequest
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
            'usuario.id' => 'required|integer|exists:users,id',
            'modulo.id' => 'required|integer|exists:modulos,id',
            'vista.id' => 'required|integer|exists:vistas,id',
            'actionvista.id' => 'required|integer|exists:actionsvistas,id',
            'tipo_tiempo.id' => 'required|integer|exists:tipos_tiempo,id',
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.id.required' => 'El campo usuario es obligatorio.',
            'usuario.id.integer' => 'El campo usuario debe ser un número entero.',
            'usuario.id.exists' => 'El usuario seleccionado no existe.',

            'modulo.id.required' => 'El campo módulo es obligatorio.',
            'modulo.id.integer' => 'El campo módulo debe ser un número entero.',
            'modulo.id.exists' => 'El módulo seleccionado no existe.',

            'vista.id.required' => 'El campo vista es obligatorio.',
            'vista.id.integer' => 'El campo vista debe ser un número entero.',
            'vista.id.exists' => 'La vista seleccionada no existe.',

            'actionvista.id.required' => 'El campo acción vista es obligatorio.',
            'actionvista.id.integer' => 'El campo acción vista debe ser un número entero.',
            'actionvista.id.exists' => 'La acción vista seleccionada no existe.',

            'tipo_tiempo.id.required' => 'El campo tipo de tiempo es obligatorio.',
            'tipo_tiempo.id.integer' => 'El campo tipo de tiempo debe ser un número entero.',
            'tipo_tiempo.id.exists' => 'El tipo de tiempo seleccionado no existe.',
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
