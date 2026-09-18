<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class StockRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'descripcion' => 'required|string|max:180|unique:stocks,descripcion',
            'telefono' => 'required|string|max:20',
            'ubicacion' => 'required|string|max:180',
            'estado.id' => 'required|integer|exists:stock_estado,id',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'descripcion.required' => 'La descripcion es requerida',
            'descripcion.string' => 'La descripcion debe ser una cadena de texto',
            'descripcion.unique' => 'La descripcion ya existe',
            'telefono.required' => 'El telefono es requerido',
            'telefono.string' => 'El telefono debe ser una cadena de texto',
            'telefono.max' => 'El telefono no debe exceder 20 caracteres',
            'ubicacion.required' => 'La ubicacion es requerida',
            'ubicacion.string' => 'La ubicacion debe ser una cadena de texto',
            'estado.id.required' => 'El estado es requerido',
            'estado.id.integer' => 'El estado debe ser un número entero',
            'estado.id.exists' => 'El estado seleccionado no existe',
        ];
    }
}
