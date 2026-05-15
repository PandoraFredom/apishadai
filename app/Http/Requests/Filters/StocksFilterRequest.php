<?php


namespace App\Http\Requests\Filters;

use App\Http\Requests\Util\FilterRequest;
use App\Models\Utils\Filter\FilterModel;

use function in_array;
use function is_string;


class StocksFilterRequest extends FilterRequest
{
    protected array $allowedKeys = ['descripcion'];

    protected array $allowedOperators = ['=', 'LIKE', 'LIKE_LEFT', 'LIKE_RIGHT', 'LIKE_ALL'];

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->normalizeDescripcionFilterItems();
    }

    public function toFilterModel(): FilterModel
    {
        $this->normalizeDescripcionFilterItems();

        return parent::toFilterModel();
    }

    private function normalizeDescripcionFilterItems(): void
    {
        if (!$this->has('filterItems')) {
            return;
        }

        $items = array_map(function ($item) {
            $key = $item['key'] ?? '';
            $operator = strtoupper($item['operator'] ?? '=');
            $value = $item['value'] ?? '';

            if ($key === 'descripcion'
                && in_array($operator, ['=', 'LIKE'], true)
                && is_string($value)
                && !str_contains($value, '%')
            ) {
                $item['operator'] = 'LIKE_ALL';
            }

            return $item;
        }, $this->input('filterItems', []));

        $this->merge(['filterItems' => $items]);
    }
}
