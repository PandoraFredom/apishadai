<?php

namespace App\Http\Requests\Promos;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class PromosRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:200',
            'descripcion' => 'required|string|max:200',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'impresiones' => 'nullable|integer|min:0',
            'valor' => 'nullable|numeric|min:0',
            'estado.id' => 'required|integer|exists:promoestado,id',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no debe exceder los 200 caracteres.',

            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no debe exceder los 200 caracteres.',

            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',

            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',

            'impresiones.integer' => 'Las impresiones deben ser un número entero.',
            'impresiones.min' => 'Las impresiones no pueden ser menores a 0.',

            'valor.numeric' => 'El valor debe ser numérico.',
            'valor.min' => 'El valor no puede ser menor a 0.',

            'estado.id.required' => 'El estado es obligatorio.',
            'estado.id.integer' => 'El estado debe ser un número entero.',
            'estado.id.exists' => 'El estado seleccionado no es válido.',
        ];
    }
}
