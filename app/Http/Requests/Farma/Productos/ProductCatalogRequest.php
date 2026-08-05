<?php

namespace App\Http\Requests\Farma\Productos;

use App\Services\Farma\ProductCatalogRegistry;
use Illuminate\Foundation\Http\FormRequest;

class ProductCatalogRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $catalog = (string) $this->route('catalogo');
        $registry = app(ProductCatalogRegistry::class);

        if ($registry->has($catalog)) {
            $this->merge($registry->sanitize($catalog, $this->all()));
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $catalog = (string) $this->route('catalogo');
        $updating = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return app(ProductCatalogRegistry::class)->rules(
            $catalog,
            $updating ? $this->integer('id') : null,
            $updating,
            $this->all(),
        );
    }

    public function messages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.unique' => 'El valor de :attribute ya existe.',
            '*.exists' => 'El valor seleccionado para :attribute no existe.',
            '*.max' => 'El campo :attribute excede la longitud permitida.',
            '*.integer' => 'El campo :attribute debe ser un número entero.',
            '*.min' => 'El campo :attribute debe ser mayor que cero.',
        ];
    }
}
