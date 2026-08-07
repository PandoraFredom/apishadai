<?php

namespace App\Http\Requests\Farma\Transferencias;

use Illuminate\Foundation\Http\FormRequest;

class TransferSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'integer', 'exists:transferencias_tipo,id'],
            'stock_para' => ['required', 'integer', 'exists:stocks,id'],
            'detalles' => ['sometimes', 'array', 'max:200'],
            'detalles.*.lote' => ['required', 'integer', 'distinct', 'exists:lotes,id'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.exists' => 'El valor seleccionado para :attribute no existe.',
            'detalles.min' => 'Agrega al menos un lote a la transferencia.',
            'detalles.*.lote.distinct' => 'Cada lote debe aparecer una sola vez.',
        ];
    }
}
