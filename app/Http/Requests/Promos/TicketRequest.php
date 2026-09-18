<?php

namespace App\Http\Requests\Promos;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class TicketRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'promocion.id' => 'required|integer|exists:promociones,id',
            'cliente.id' => 'required|integer|exists:clientes,id',
            'stock.id' => 'required|integer|exists:stocks,id',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'promocion.id.required' => 'El campo promocion.id es obligatorio.',
            'promocion.id.integer' => 'El campo promocion.id debe ser un número entero.',
            'promocion.id.exists' => 'El valor proporcionado para promocion.id no existe.',

            'cliente.id.required' => 'El campo cliente.id es obligatorio.',
            'cliente.id.integer' => 'El campo cliente.id debe ser un número entero.',
            'cliente.id.exists' => 'El valor proporcionado para cliente.id no existe.',

            'stock.id.required' => 'El campo stock.id es obligatorio.',
            'stock.id.integer' => 'El campo stock.id debe ser un número entero.',
            'stock.id.exists' => 'El valor proporcionado para stock.id no existe.',
        ];
    }
}
