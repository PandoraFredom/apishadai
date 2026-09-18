<?php

namespace App\Http\Requests\Farma\Productos;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $this->merge(['id' => $this->route('id')]);
        }

        $data = [];

        foreach (['codigo', 'codigobar', 'descripcion'] as $field) {
            if (! $this->exists($field)) {
                continue;
            }

            if ($field === 'codigobar' && ! filled($this->input($field))) {
                $data[$field] = null;

                continue;
            }

            $value = strip_tags((string) $this->input($field));
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
            $data[$field] = trim($value);
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $updating = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $id = $updating ? (int) $this->route('id') : null;

        return [
            ...($updating ? ['id' => ['required', 'integer', 'exists:productos,id']] : []),
            'categoria' => ['required', 'integer', 'exists:prod_categorias,id'],
            'laboratorio' => ['required', 'integer', 'exists:laboratorios,id'],
            'unidad' => ['required', 'integer', 'exists:prod_unidades,id'],
            'familia' => ['required', 'integer', 'exists:familia,id'],
            'codigo' => [
                'required',
                'string',
                'max:100',
                Rule::unique('productos', 'codigo')->ignore($id),
            ],
            'codigobar' => [
                'nullable',
                'string',
                'max:150',
                Rule::unique('productos', 'codigobar')->ignore($id),
            ],
            'descripcion' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'integer', 'exists:prod_estado,id'],
            'imagen' => [
                'nullable',
                'string',
                'max:1398104',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || base64_decode($value, true) === false) {
                        $fail('La imagen debe ser una cadena base64 válida.');
                    }
                },
            ],
            'principios_activos' => ['sometimes', 'array'],
            'principios_activos.*' => ['integer', 'distinct', 'exists:principal_activos,id'],
        ];
    }

    protected function customMessages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.exists' => 'El valor seleccionado para :attribute no existe.',
            '*.unique' => 'El valor de :attribute ya está registrado.',
            '*.max' => 'El campo :attribute excede la longitud permitida.',
            'imagen.max' => 'La imagen no debe exceder 1 MB.',
        ];
    }
}
