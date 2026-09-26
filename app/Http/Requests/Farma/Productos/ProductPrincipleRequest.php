<?php

namespace App\Http\Requests\Farma\Productos;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ProductPrincipleRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'pactivo' => [
                'required',
                'integer',
                'min:1',
                'exists:principal_activos,id',
                Rule::unique('prod_activo', 'pactivo')
                    ->where(fn ($query) => $query->where('producto', $this->route('id'))),
            ],
        ];
    }

    protected function customMessages(): array
    {
        return ['pactivo.unique' => 'El principio activo ya está asociado con este producto.'];
    }
}
