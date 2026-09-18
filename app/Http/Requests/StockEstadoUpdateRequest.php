<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StockEstadoUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:stock_estado,id',
            'descripcion' => [
                'required',
                'string',
                'max:200',
                Rule::unique('stock_estado', 'descripcion')->ignore($this->input('id')),
            ],
        ];
    }

    protected function customMessages(): array
    {
        return [
            'id.required' => 'El id del estado es requerido',
            'id.integer' => 'El id del estado debe ser un número entero',
            'id.exists' => 'El estado seleccionado no existe',
            'descripcion.required' => 'La descripcion es requerida',
            'descripcion.string' => 'La descripcion debe ser una cadena de texto',
            'descripcion.max' => 'La descripcion no debe exceder 200 caracteres',
            'descripcion.unique' => 'La descripcion ya existe',
        ];
    }
}
