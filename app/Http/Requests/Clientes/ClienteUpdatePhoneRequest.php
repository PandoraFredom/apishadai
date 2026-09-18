<?php

namespace App\Http\Requests\Clientes;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ClienteUpdatePhoneRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:clientes,id',
            'telefono' => 'required|string|min:8|max:8|unique:clientes,telefono',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'id.required' => 'El campo id es obligatorio.',
            'id.integer' => 'El campo id debe ser un número entero.',
            'id.exists' => 'El cliente no existe.',

            'telefono.required' => 'El campo teléfono es obligatorio.',
            'telefono.string' => 'El campo teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El campo teléfono no debe exceder los 10 caracteres.',
            'telefono.unique' => 'El teléfono ya está en uso por otro cliente.',
        ];
    }
}
