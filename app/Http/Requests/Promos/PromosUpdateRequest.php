<?php

namespace App\Http\Requests\Promos;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class PromosUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => '  required|integer|exists:promociones,id',
            'nombre' => 'required|string|max:200',
            'descripcion' => 'required|string|max:200',
            'impresiones' => 'nullable|integer|min:0',
            'valor' => 'nullable|numeric|min:0',
            'estado.id' => 'required|integer|exists:promoestado,id',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'id.required' => 'El campo id es obligatorio.',
            'id.integer' => 'El campo id debe ser un número entero.',
            'id.exists' => 'La promoción con el id proporcionado no existe.',

            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
            'nombre.max' => 'El campo nombre no debe exceder los 200 caracteres.',

            'descripcion.required' => 'El campo descripción es obligatorio.',
            'descripcion.string' => 'El campo descripción debe ser una cadena de texto.',
            'descripcion.max' => 'El campo descripción no debe exceder los 200 caracteres.',

            'impresiones.integer' => 'El campo impresiones debe ser un número entero.',
            'impresiones.min' => 'El campo impresiones no puede ser menor a 0.',

            'valor.numeric' => 'El campo valor debe ser numérico.',
            'valor.min' => 'El campo valor no puede ser menor a 0.',

            'estado.id.required' => 'El campo estado.id es obligatorio.',
            'estado.id.integer' => 'El campo estado.id debe ser un número entero.',
            'estado.id.exists' => 'El estado con el id proporcionado no existe.',
        ];
    }
}
