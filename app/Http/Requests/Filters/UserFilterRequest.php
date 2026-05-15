<?php

namespace App\Http\Requests\Filters;

use App\Http\Requests\Util\FilterRequest;
use App\Models\Utils\Filter\FilterModel;
use Illuminate\Support\Facades\Log;

use function in_array;
use function is_string;

class UserFilterRequest extends FilterRequest
{

    protected array $allowedKeys = ['nombre'];


    protected array $allowedOperators = ['=','LIKE_ALL','LIKE','LIKE_LEFT','LIKE_RIGHT'];

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->normalizeNombreFilterItems();
    }

    public function toFilterModel(): FilterModel
    {
        $this->normalizeNombreFilterItems();

        return parent::toFilterModel();
    }

    private function normalizeNombreFilterItems(): void
    {
        if (!$this->has('filterItems')) {
            return;
        }
        $items = array_map(function ($item) {
            $key = $item['key'] ?? '';
            $operator = strtoupper($item['operator'] ?? '=');
            $value = $item['value'] ?? '';

            if ($key === 'nombre'
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
