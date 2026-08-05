<?php

namespace App\Http\Requests\Farma\Compras;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nro' => trim(strip_tags((string) $this->input('nro'))),
            'nota' => filled($this->input('nota')) ? trim(strip_tags((string) $this->input('nota'))) : null,
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'tipo' => ['required', 'integer', 'exists:compra_tipo,int'],
            'proveedor' => ['required', 'integer', 'exists:proveedores,id'],
            'plazo' => ['required', 'integer', 'min:0', 'max:3650'],
            'nro' => ['required', 'string', 'max:255', Rule::unique('compras', 'nro')->where('proveedor', $this->integer('proveedor'))->ignore($id)],
            'nota' => ['nullable', 'string', 'max:5000'],
            'img' => ['nullable', 'string', 'max:1398104', static function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || base64_decode($value, true) === false) {
                    $fail('El documento debe ser una imagen base64 válida.');
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.exists' => 'El valor seleccionado para :attribute no existe.',
            '*.unique' => 'La factura ya está registrada para este proveedor.',
            'img.max' => 'El documento escaneado no debe exceder 1 MB.',
        ];
    }
}
