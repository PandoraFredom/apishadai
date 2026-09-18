<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UserUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:users,id',
            'rol.id' => 'required|integer|exists:roles,id',
            'estado.id' => 'required|integer|exists:user_estados,id',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'id.required' => 'El campo id es obligatorio.',
            'id.integer' => 'El campo id debe ser un número entero.',
            'id.exists' => 'El usuario seleccionado no es válido.',

            'rol.id.required' => 'El campo rol es obligatorio.',
            'rol.id.integer' => 'El campo rol debe ser un número entero.',
            'rol.id.exists' => 'El rol seleccionado no es válido.',
            'estado.id.required' => 'El campo estado es obligatorio.',
            'estado.id.integer' => 'El campo estado debe ser un número entero.',
            'estado.id.exists' => 'El estado seleccionado no es válido.',
        ];
    }
}
