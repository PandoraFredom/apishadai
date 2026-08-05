<?php

namespace App\Http\Requests\Farma\Compras;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['descripcion' => trim(strip_tags((string) $this->input('descripcion')))]);
    }

    public function rules(): array
    {
        $table = $this->route('catalogoCompra') === 'tipos-compra' ? 'compra_tipo' : 'transc_tipo';
        $key = $table === 'compra_tipo' ? 'int' : 'id';

        return [
            'id' => [$this->isMethod('PUT') ? 'required' : 'nullable', 'integer'],
            'descripcion' => ['required', 'string', 'max:255', Rule::unique($table, 'descripcion')->ignore($this->integer('id'), $key)],
        ];
    }
}
