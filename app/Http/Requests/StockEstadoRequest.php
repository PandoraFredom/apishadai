<?php

namespace App\Http\Requests;

class StockEstadoRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'descripcion' => 'required|string|max:200|unique:stock_estado,descripcion',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'descripcion.required' => 'La descripcion es requerida',
            'descripcion.string' => 'La descripcion debe ser una cadena de texto',
            'descripcion.max' => 'La descripcion no debe exceder 200 caracteres',
            'descripcion.unique' => 'La descripcion ya existe',
        ];
    }
}
