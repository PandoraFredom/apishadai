<?php

namespace App\Http\Requests\Permiso;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class PermisoRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
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

    protected function customMessages(): array
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
}
