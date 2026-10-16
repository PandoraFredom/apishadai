<?php

namespace App\Http\Requests\Pos;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class PosDiscountRequest extends BaseRequest
{
    public function rules(): array
    {
        if ($this->isMethod('DELETE')) {
            return ['revision_esperada' => ['required', 'integer', 'min:1']];
        }
        $rules = [
            'stock_id' => ['sometimes', 'nullable', 'integer', 'exists:stocks,id'],
            'alcance' => ['required', Rule::in(['CATEGORIA', 'PRODUCTO'])],
            'referencia_id' => ['required', 'integer', 'min:1', Rule::exists($this->input('alcance') === 'CATEGORIA' ? 'prod_categorias' : 'productos', 'id')],
            'activo' => ['required', 'boolean'],
            'revision_esperada' => [$this->isMethod('POST') ? 'missing' : 'required', 'integer', 'min:1'],
        ];
        if ($this->has('regla')) {
            return $rules + [
                'regla' => ['required', 'array:modo'],
                'regla.modo' => ['required', Rule::in(['HEREDAR', 'LOTE', 'EXCLUIR'])],
                'reglas' => ['missing'],
            ];
        }
        $rules += [
            'reglas' => ['required', 'array:dto1,dto2,dto3'],
        ];
        foreach (['dto1', 'dto2', 'dto3'] as $type) {
            $rules['reglas.'.$type] = ['required', 'array:modo,porcentaje'];
            $rules['reglas.'.$type.'.modo'] = ['required', Rule::in(['HEREDAR', 'LOTE', 'EXCLUIR', 'PORCENTAJE'])];
            $rules['reglas.'.$type.'.porcentaje'] = $this->input('reglas.'.$type.'.modo') === 'PORCENTAJE'
                ? ['required', 'numeric', 'between:0,100', 'decimal:0,4'] : ['prohibited'];
        }

        return $rules;
    }
}
