<?php

namespace App\Http\Requests\Farma\Distribucion;

use Illuminate\Foundation\Http\FormRequest;

class DistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'dto1' => $this->input('dto1', 0),
            'dto2' => $this->input('dto2', 0),
            'dto3' => $this->input('dto3', 0),
            'dto4' => $this->input('dto4', 0),
        ]);
    }

    public function rules(): array
    {
        return [
            'precio' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'dto1' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'dto2' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'dto3' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'dto4' => ['required', 'numeric', 'min:0', 'max:99.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'precio.required' => 'El precio es obligatorio.',
            'precio.gt' => 'El precio debe ser mayor que cero.',
            'dto*.max' => 'Los descuentos no pueden ser mayores que 99.99%.',
        ];
    }
}
