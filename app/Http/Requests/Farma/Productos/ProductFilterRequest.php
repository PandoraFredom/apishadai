<?php

namespace App\Http\Requests\Farma\Productos;

use App\Http\Requests\Util\FilterRequest;
use Illuminate\Validation\Rule;
use function in_array;
use function strlen;

class ProductFilterRequest extends FilterRequest
{
    protected array $allowedOperators = [
        'codigo' => ['LIKE_ALL', 'OR'],
        'codigobar' => ['LIKE_ALL', 'OR'],
        'descripcion' => ['LIKE_ALL', 'OR'],
        'laboratorio' => ['=', 'AND'],
        'familia' => ['=', 'AND'],
        'categoria' => ['=', 'AND'],
    ];

    public function rules(): array
    {
        $rules = parent::rules();

        return [
            ...$rules,
            'filterItems.*.value' => Rule::forEach(function ($value, string $attribute) use ($rules): array {
                $key = $this->input(substr($attribute, 0, -strlen('value')).'key');

                return in_array($key, ['laboratorio', 'familia', 'categoria'], true)
                    ? ['required', 'integer', 'min:1']
                    : $rules['filterItems.*.value'];
            }),
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
