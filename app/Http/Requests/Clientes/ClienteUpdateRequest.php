<?php

namespace App\Http\Requests\Clientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ClienteUpdateRequest extends FormRequest
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
            'id' => 'required|integer|exists:clientes,id',
            'docid' => 'required|string|max:20|unique:clientes,docid',
            'pnombre' => 'required|string|max:50',
            'snombre' => 'required|string|max:50',
            'papellido' => 'required|string|max:50',
            'spaellido' => 'required|string|max:50',
            'edad' => 'required|integer|min:1|max:2',
            'telefono' => "required|string|max:10|unique:clientes,telefono",
            'genero' => 'required|string|max:1',
            'municipio.id' => 'required|integer|exists:municipios,id',
            'departamento.id' => 'required|integer|exists:departamentos,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'El campo ID es obligatorio.',
            'id.integer' => 'El campo ID debe ser un número entero.',
            'id.exists' => 'El ID proporcionado no existe.',

            'docid.required' => 'El campo Documento de Identidad es obligatorio.',
            'docid.string' => 'El campo Documento de Identidad debe ser una cadena de texto.',
            'docid.max' => 'El campo Documento de Identidad no debe exceder los 20 caracteres.',
            'docid.unique' => 'El Documento de Identidad ya está en uso.',

            'pnombre.required' => 'El campo Primer Nombre es obligatorio.',
            'pnombre.string' => 'El campo Primer Nombre debe ser una cadena de texto.',
            'pnombre.max' => 'El campo Primer Nombre no debe exceder los 50 caracteres.',

            'snombre.required' => 'El campo Segundo Nombre es obligatorio.',
            'snombre.string' => 'El campo Segundo Nombre debe ser una cadena de texto.',
            'snombre.max' => 'El campo Segundo Nombre no debe exceder los 50 caracteres.',

            'papellido.required' => 'El campo Primer Apellido es obligatorio.',
            'papellido.string' => 'El campo Primer Apellido debe ser una cadena de texto.',
            'papellido.max' => 'El campo Primer Apellido no debe exceder los 50 caracteres.',

            'spaellido.required' => 'El campo Segundo Apellido es obligatorio.',
            'spaellido.string' => 'El campo Segundo Apellido debe ser una cadena de texto.',
            'spaellido.max' => 'El campo Segundo Apellido no debe exceder los 50 caracteres.',

            'edad.required' => 'El campo Edad es obligatorio.',
            'edad.integer' => 'El campo Edad debe ser un número entero.',
            'edad.min' => 'El campo Edad debe ser al menos 1.',
            'edad.max' => 'El campo Edad no debe exceder los 2 dígitos.',

            'telefono.required' => 'El campo Teléfono es obligatorio.',
            'telefono.string' => 'El campo Teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El campo Teléfono no debe exceder los 10 caracteres.',
            'telefono.unique' => 'El Teléfono ya está en uso.',

            'genero.required' => 'El campo Género es obligatorio.',
            'genero.string' => 'El campo Género debe ser una cadena de texto.',
            'genero.max' => 'El campo Género no debe exceder 1 carácter.',
            'municipio.id.required' => 'El campo Municipio es obligatorio.',
            'municipio.id.integer' => 'El campo Municipio debe ser un número entero.',
            'municipio.id.exists' => 'El Municipio seleccionado no es válido.',

            'departamento.id.required' => 'El campo Departamento es obligatorio.',
            'departamento.id.integer' => 'El campo Departamento debe ser un número entero.',
            'departamento.id.exists' => 'El Departamento seleccionado no es válido.',
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
