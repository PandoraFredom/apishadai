<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UserRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:150|unique:users,nombre',
            'rol.id' => 'required|integer|exists:roles,id',
            'name' => 'required|string|max:80|min:5',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|max:185|min:5',
            'estado.id' => 'required|integer|exists:user_estados,id',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 150 caracteres.',
            'nombre.unique' => 'El nombre ya está en uso.',

            'rol.id.required' => 'El campo rol es obligatorio.',
            'rol.id.integer' => 'El campo rol debe ser un número entero.',
            'rol.id.exists' => 'El rol seleccionado no es válido.',

            'name.required' => 'El campo name es obligatorio.',
            'name.string' => 'El campo name debe ser una cadena de texto.',
            'name.max' => 'El campo name no debe exceder los 80 caracteres.',
            'name.min' => 'El campo name debe tener al menos 5 caracteres.',

            'email.required' => 'El campo email es obligatorio.',
            'email.string' => 'El campo email debe ser una cadena de texto.',
            'email.email' => 'El campo email debe ser una dirección de correo electrónico válida.',
            'email.unique' => 'El email ya está en uso.',

            'password.required' => 'El campo password es obligatorio.',
            'password.string' => 'El campo password debe ser una cadena de texto.',
            'password.max' => 'El campo password no debe exceder los 185 caracteres.',
            'password.min' => 'El campo password debe tener al menos 5 caracteres.',

            'estado.id.required' => 'El campo estado es obligatorio.',
            'estado.id.integer' => 'El campo estado debe ser un número entero.',
            'estado.id.exists' => 'El estado seleccionado no es válido.',
        ];
    }
}
