<?php

namespace App\Http\Requests\Clientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ClienteRequest extends FormRequest
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
            'docid' => 'required|string|max:13|unique:clientes,docid',
            'pnombre' => 'required|string|max:50',
            'snombre' => 'required|string|max:50',
            'papellido' => 'required|string|max:50',
            'spaellido' => 'required|string|max:50',
            'edad' => 'required|integer',
            'telefono' => 'required|string|unique:clientes,telefono|max:10',
            'genero' => 'required|string|max:1',
            'municipio.id' => 'required|integer|exists:municipios,id',
            'departamento.id' => 'required|integer|exists:departamentos,id',
        ];
    }


    public function messages()
    {
        return [
            // Docid
            'docid.required' => 'El documento de identidad es obligatorio.',
            'docid.string' => 'El documento de identidad debe ser texto.',
            'docid.max' => 'El documento de identidad no puede exceder 13 caracteres.',
            'docid.unique' => 'El documento de identidad ya est� registrado.',

            // Primer nombre
            'pnombre.required' => 'El primer nombre es obligatorio.',
            'pnombre.string' => 'El primer nombre debe ser texto.',
            'pnombre.max' => 'El primer nombre no puede exceder 50 caracteres.',

            // Segundo nombre
            'snombre.required' => 'El segundo nombre es obligatorio.',
            'snombre.string' => 'El segundo nombre debe ser texto.',
            'snombre.max' => 'El segundo nombre no puede exceder 50 caracteres.',

            // Primer apellido
            'papellido.required' => 'El primer apellido es obligatorio.',
            'papellido.string' => 'El primer apellido debe ser texto.',
            'papellido.max' => 'El primer apellido no puede exceder 50 caracteres.',

            // Segundo apellido
            'spaellido.required' => 'El segundo apellido es obligatorio.',
            'spaellido.string' => 'El segundo apellido debe ser texto.',
            'spaellido.max' => 'El segundo apellido no puede exceder 50 caracteres.',

            // Edad
            'edad.required' => 'La edad es obligatoria.',
            'edad.integer' => 'La edad debe ser un n�mero entero.',

            // Tel�fono
            'telefono.required' => 'El tel�fono es obligatorio.',
            'telefono.string' => 'El tel�fono debe ser texto.',
            'telefono.unique' => 'El tel�fono ya est� registrado.',
            'telefono.max' => 'El tel�fono no puede exceder 10 caracteres.',

            // G�nero
            'genero.required' => 'El g�nero es obligatorio.',
            'genero.string' => 'El g�nero debe ser texto.',
            'genero.max' => 'El g�nero no puede exceder 1 car�cter.',

            // Municipio
            'municipio.id.required' => 'El municipio es obligatorio.',
            'municipio.id.integer' => 'El municipio debe ser un n�mero entero.',
            'municipio.id.exists' => 'El municipio seleccionado no existe.',

            // Departamento
            'departamento.id.required' => 'El departamento es obligatorio.',
            'departamento.id.integer' => 'El departamento debe ser un n�mero entero.',
            'departamento.id.exists' => 'El departamento seleccionado no existe.',
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
