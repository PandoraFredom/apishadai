<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatchTokenUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario' => 'sometimes|required|integer|exists:users,id',
            'device' => 'sometimes|required|integer|exists:device,id',
            'token' => 'sometimes|required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.integer' => 'El usuario debe ser un número entero',
            'usuario.exists' => 'El usuario no existe',
            'device.integer' => 'El dispositivo debe ser un número entero',
            'device.exists' => 'El dispositivo no existe',
            'token.max' => 'El token no puede exceder 1000 caracteres',
        ];
    }
}
