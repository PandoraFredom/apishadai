<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class AccionesVistaRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vista.id' => 'required|integer',
            'codigo' => 'required|string|max:20',
            'nombre' => 'required|string|max:80',
        ];
    }

    // messages
    protected function customMessages(): array
    {
        return [
            'vista.id.required' => 'El campo vista es obligatorio.',
            'vista.id.integer' => 'El campo vista debe ser un número entero.',

            'codigo.required' => 'El campo código es obligatorio.',
            'codigo.string' => 'El campo código debe ser una cadena de texto.',
            'codigo.max' => 'El campo código no debe exceder los 20 caracteres.',

            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 100 caracteres.',
        ];
    }
}
