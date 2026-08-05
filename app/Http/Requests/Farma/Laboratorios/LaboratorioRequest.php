<?php

namespace App\Http\Requests\Farma\Laboratorios;

use Illuminate\Foundation\Http\FormRequest;

class LaboratorioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:180', 'unique:laboratorios,nombre'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'imagen' => [
                'nullable',
                'string',
                'max:2796204',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || base64_decode($value, true) === false) {
                        $fail('La imagen del laboratorio debe ser una cadena base64 válida.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del laboratorio es requerido.',
            'nombre.max' => 'El nombre del laboratorio no debe exceder 180 caracteres.',
            'nombre.unique' => 'El nombre del laboratorio ya existe.',
            'telefono.max' => 'El teléfono no debe exceder 20 caracteres.',
            'direccion.max' => 'La dirección no debe exceder 255 caracteres.',
            'imagen.max' => 'La imagen no debe exceder 2 MB.',
        ];
    }
}
