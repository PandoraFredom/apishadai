<?php

namespace App\Http\Requests\Vista;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class VistaRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'modulo.id' => 'required|integer|exists:modulos,id',
            'nombre' => 'required|string|max:50',
            'codigo' => 'required|string|max:10|unique:vistas,codigo',
            'estado.id' => 'required|integer|exists:vista_estados,id',
        ];
    }

    protected function customMessages(): array
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
}
