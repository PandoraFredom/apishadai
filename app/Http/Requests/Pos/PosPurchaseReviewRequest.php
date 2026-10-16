<?php

namespace App\Http\Requests\Pos;

use App\Http\Requests\BaseRequest;

class PosPurchaseReviewRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->route('kind') !== null) {
            return ['search' => ['nullable', 'string', 'max:180']];
        }
        $rules = ['page' => ['sometimes', 'integer', 'min:1', 'max:100000']];
        foreach (['stock_id', 'supplier_id', 'product_id'] as $field) {
            $rules[$field] = ['nullable', 'string', 'regex:/\A[1-9][0-9]{0,19}\z/'];
        }
        $rules['date_from'] = ['nullable', 'date_format:Y-m-d'];
        $rules['date_to'] = ['nullable', 'date_format:Y-m-d', ...($this->filled('date_from') ? ['after_or_equal:date_from'] : [])];
        $rules['status'] = ['nullable', 'in:accepted,rejected,cancelled'];

        return $rules;
    }
}
