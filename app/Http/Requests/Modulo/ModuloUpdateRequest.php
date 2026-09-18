<?php

namespace App\Http\Requests\Modulo;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ModuloUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:modulos,id',
            'estado.id' => 'required|integer|exists:modulo_estados,id',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'id.required' => 'El campo id es obligatorio.',
            'id.integer' => 'El campo id debe ser un número entero.',
            'id.exists' => 'El id proporcionado no existe en la base de datos.',

            'estado.id.required' => 'El campo estado.id es obligatorio.',
            'estado.id.integer' => 'El campo estado.id debe ser un número entero.',
            'estado.id.exists' => 'El estado.id proporcionado no existe en la base de datos.',
        ];
    }
}
