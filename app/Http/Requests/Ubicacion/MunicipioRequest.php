<?php

namespace App\Http\Requests\Ubicacion;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class MunicipioRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:80|unique:municipios,nombre',
            'departamento.id' => 'required|integer|exists:departamentos,id',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'nombre.required' => 'El nombre es requerido',
            'nombre.string' => 'El nombre debe ser una cadena de texto',
            'nombre.unique' => 'El nombre ya existe',

            'departamento.id.required' => 'El campo departamento es obligatorio.',
            'departamento.id.integer' => 'El campo departamento debe ser un número entero.',
            'departamento.id.exists' => 'El departamento seleccionado no existe.',
        ];
    }
}
