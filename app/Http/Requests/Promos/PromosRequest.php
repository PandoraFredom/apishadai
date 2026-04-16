<?php

namespace App\Http\Requests\Promos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class PromosRequest extends FormRequest
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
            'nombre' => 'required|string|max:200',
            'descripcion' => 'required|string|max:200',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'impresiones' => 'nullable|integer|min:0',
            'valor' => 'nullable|numeric|min:0',
            'estado.id' => 'required|integer|exists:promoestado,id',
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no debe exceder los 200 caracteres.',

            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no debe exceder los 200 caracteres.',

            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',

            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',

            'impresiones.integer' => 'Las impresiones deben ser un número entero.',
            'impresiones.min' => 'Las impresiones no pueden ser menores a 0.',

            'valor.numeric' => 'El valor debe ser numérico.',
            'valor.min' => 'El valor no puede ser menor a 0.',

            'estado.id.required' => 'El estado es obligatorio.',
            'estado.id.integer' => 'El estado debe ser un número entero.',
            'estado.id.exists' => 'El estado seleccionado no es válido.',
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
