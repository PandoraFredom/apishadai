<?php

namespace App\Http\Requests\Modulo;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ModuloRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|unique:modulos,nombre',
            'codigo' => 'required|string|unique:modulos,codigo',
            'estado.id' => 'required|integer|exists:modulo_estados,id',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.string' => 'El nombre debe ser una cadena de texto',
            'nombre.unique' => 'El nombre ya está en uso',

            'codigo.required' => 'El código es obligatorio',
            'codigo.string' => 'El código debe ser una cadena de texto',
            'codigo.unique' => 'El código ya está en uso',

            'estado.id.required' => 'El estado es obligatorio',
            'estado.id.integer' => 'El estado debe ser un entero',
            'estado.id.exists' => 'El estado no existe',
        ];
    }
}
