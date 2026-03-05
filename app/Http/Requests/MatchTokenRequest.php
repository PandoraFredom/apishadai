<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatchTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario' => 'required|integer|exists:users,id',
            'device' => 'required|integer|exists:device,id',
            'token' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.required' => 'El usuario es requerido',
            'usuario.integer' => 'El usuario debe ser un número entero',
            'usuario.exists' => 'El usuario no existe',
            'device.required' => 'El dispositivo es requerido',
            'device.integer' => 'El dispositivo debe ser un número entero',
            'device.exists' => 'El dispositivo no existe',
            'token.required' => 'El token es requerido',
            'token.max' => 'El token no puede exceder 1000 caracteres',
        ];
    }
}
