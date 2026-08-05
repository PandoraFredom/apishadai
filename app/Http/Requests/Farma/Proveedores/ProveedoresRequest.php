<?php

namespace App\Http\Requests\Farma\Proveedores;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ProveedoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:180|unique:proveedores,nombre',
            'telefono' => 'required|string|max:20',
            'direccion' => 'required|string|max:255',
            'imagen' => [
                'nullable',
                'string',
                'max:2796204',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || base64_decode($value, true) === false) {
                        $fail('La imagen del proveedor debe ser una cadena base64 válida.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proveedor es requerido',
            'nombre.string' => 'El nombre del proveedor debe ser una cadena de texto',
            'nombre.max' => 'El nombre del proveedor no debe exceder 180 caracteres',
            'nombre.unique' => 'El nombre del proveedor ya existe',
            'telefono.required' => 'El telefono del proveedor es requerido',
            'telefono.string' => 'El telefono del proveedor debe ser una cadena de texto',
            'telefono.max' => 'El telefono del proveedor no debe exceder 20 caracteres',
            'direccion.required' => 'La direccion del proveedor es requerida',
            'direccion.string' => 'La direccion del proveedor debe ser una cadena de texto',
            'direccion.max' => 'La direccion del proveedor no debe exceder 255 caracteres',
            'imagen.string' => 'La imagen del proveedor debe ser una cadena de texto',
            'imagen.max' => 'La imagen del proveedor no debe exceder 2 MB',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = [
            'message' => $validator->errors()->first(),
            'code' => 400,
            'data' => null,
        ];
        http_response_code(400);
        exit(json_encode($response));
    }
}
