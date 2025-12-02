<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'id'        => 'required|integer|exists:users,id',
            'rol.id'    => 'required|integer|exists:roles,id',
            'estado.id' => 'required|integer|exists:user_estados,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'        => 'El campo id es obligatorio.',
            'id.integer'         => 'El campo id debe ser un número entero.',
            'id.exists'          => 'El usuario seleccionado no es válido.',

            'rol.id.required'    => 'El campo rol es obligatorio.',
            'rol.id.integer'     => 'El campo rol debe ser un número entero.',
            'rol.id.exists'      => 'El rol seleccionado no es válido.',

            'estado.id.required' => 'El campo estado es obligatorio.',
            'estado.id.integer'  => 'El campo estado debe ser un número entero.',
            'estado.id.exists'   => 'El estado seleccionado no es válido.',
        ];
    }
}
