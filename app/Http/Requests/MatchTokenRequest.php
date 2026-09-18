<?php

namespace App\Http\Requests;

class MatchTokenRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'usuario' => 'required|integer|exists:users,id',
            'device' => 'required|integer|exists:device,id',
            'token' => 'required|string|max:1000',
        ];
    }

    protected function customMessages(): array
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
