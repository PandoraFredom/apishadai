<?php

namespace App\Http\Requests\Farma\Productos;

use App\DTO\Farma\FamiliaData;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class FamiliaRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $this->merge(['id' => $this->route('id')]);
        }

        $this->merge(FamiliaData::sanitize($this->all()));
    }

    public function rules(): array
    {
        $updating = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $id = $updating ? (int) $this->route('id') : null;

        return [
            ...($updating ? ['id' => ['required', 'integer', 'exists:familia,id']] : []),
            'presentacion' => ['required', 'integer', 'exists:fam_presentacion,id'],
            'administracion' => ['required', 'integer', 'exists:fam_administracion,id'],
            'descripcion' => [
                'required',
                'string',
                'max:200',
                Rule::unique('familia', 'descripcion')
                    ->where(fn ($query) => $query
                        ->where('presentacion', $this->integer('presentacion'))
                        ->where('administracion', $this->integer('administracion')))
                    ->ignore($id),
            ],
        ];
    }

    protected function customMessages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            'descripcion.unique' => 'Ya existe una familia con la misma descripcion, tipo de administracion y tipo de presentacion.',
            '*.exists' => 'El valor seleccionado para :attribute no existe.',
            '*.max' => 'El campo :attribute excede la longitud permitida.',
            '*.integer' => 'El campo :attribute debe ser un número entero.',
            '*.min' => 'El campo :attribute debe ser mayor que cero.',
        ];
    }
}
