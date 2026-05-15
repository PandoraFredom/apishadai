<?php


namespace App\Http\Requests\Filters;

use App\Http\Requests\Util\FilterRequest;
use function in_array;
use function is_string;

class ClientesFilterRequest extends FilterRequest
{
    protected array $allowedKeys = ['docid', 'pnombre'];


    protected array $allowedOperators = ['=', 'LIKE', 'LIKE_LEFT', 'LIKE_RIGHT', 'LIKE_ALL'];

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (!$this->has('filterItems')) {
            return;
        }

        $items = array_map(function ($item) {
            $key = $item['key'] ?? '';
            $operator = strtoupper($item['operator'] ?? '=');
            $value = $item['value'] ?? '';

            if (in_array($key, ['docid', 'pnombre'], true)
                && in_array($operator, ['=', 'LIKE'], true)
                && is_string($value)
                && !str_contains($value, '%')
            ) {
                $item['operator'] = $key === 'docid' ? 'LIKE_RIGHT' : 'LIKE_ALL';
            }

            return $item;
        }, $this->input('filterItems', []));

        $this->merge(['filterItems' => $items]);
    }
}
