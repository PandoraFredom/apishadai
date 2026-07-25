<?php

namespace App\Http\Requests\Farma\Proveedores;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedoresUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:proveedores,id',
            'nombre' => [
                'required',
                'string',
                'max:180',
                Rule::unique('proveedores', 'nombre')->ignore($this->input('id')),
            ],
            'telefono' => 'required|string|max:20',
            'direccion' => 'required|string|max:255',
            'imagen' => 'nullable|string|base64|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'El id del proveedor es requerido',
            'id.integer' => 'El id del proveedor debe ser un numero entero',
            'id.exists' => 'El proveedor seleccionado no existe',
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
            'imagen.max' => 'La imagen del proveedor no debe exceder 255 caracteres',
            'imagen.base64' => 'La imagen del proveedor debe ser una cadena de texto en formato base64',
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
