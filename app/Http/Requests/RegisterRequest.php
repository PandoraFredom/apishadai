<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
          return [
            'nombre'    => 'required|string|unique:users,nombre',
            'rol.id'    => 'required|integer|exists:roles,id',
            'name'      => 'required|string|min:5',
            'email'     => 'required|string|email|unique:users,email',
            'password'  => 'required|string|min:5',
            'estado.id' => 'required|integer|exists:user_estados,id',
        ];
    }
    public function messages(): array
    {
        return [
            'nombre.required'    => 'El campo nombre es obligatorio.',
            'nombre.string'      => 'El campo nombre debe ser una cadena de texto.',
            'nombre.unique'      => 'El nombre ya está en uso.',

            'rol.id.required'    => 'El campo rol es obligatorio.',
            'rol.id.integer'     => 'El campo rol debe ser un número entero.',
            'rol.id.exists'      => 'El rol seleccionado no es válido.',

            'name.required'      => 'El campo name es obligatorio.',
            'name.string'        => 'El campo name debe ser una cadena de texto.',
            'name.min'           => 'El campo name debe tener al menos 5 caracteres.',

            'email.required'     => 'El campo email es obligatorio.',
            'email.string'       => 'El campo email debe ser una cadena de texto.',
            'email.email'        => 'El campo email debe ser una dirección de correo electrónico válida.',
            'email.unique'       => 'El email ya está en uso.',

            'password.required'  => 'El campo password es obligatorio.',
            'password.string'    => 'El campo password debe ser una cadena de texto.',
            'password.min'       => 'El campo password debe tener al menos 5 caracteres.',

            'estado.id.required' => 'El campo estado es obligatorio.',
            'estado.id.integer'  => 'El campo estado debe ser un número entero.',
            'estado.id.exists'   => 'El estado seleccionado no es válido.',
        ];
    }


}
