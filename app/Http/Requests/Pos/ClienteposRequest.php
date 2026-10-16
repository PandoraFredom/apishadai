<?php

namespace App\Http\Requests\Pos;

use App\DTO\ClienteposDTO;
use App\Http\Requests\BaseRequest;

class ClienteposRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(ClienteposDTO::normalize($this->only(['nombre', 'rtn', 'telefono', 'direccion', 'tipo_documento', 'fecha_nacimiento'])));
    }

    protected function allowsLegacyDocument(): bool
    {
        return false;
    }

    public function rules(): array
    {
        $legacy = $this->allowsLegacyDocument();
        $type = $this->input('tipo_documento');
        $documentRules = $legacy ? ['nullable', 'string', 'max:20'] : [
            'required', 'string', 'regex:'.($type === 'IDENTIDAD' ? '/\A(?!0+\z)[0-9]{13}\z/' : '/\A(?!0+\z)[0-9]{14}\z/'),
        ];

        return [
            'nombre' => ['required', 'string', 'max:180'],
            'tipo_documento' => [$legacy ? 'nullable' : 'required', 'in:IDENTIDAD,RTN'],
            'rtn' => [...$documentRules, 'unique:clientepos,rtn'],
            'fecha_nacimiento' => [$type === 'IDENTIDAD' ? 'required' : 'nullable', 'date_format:Y-m-d', 'before_or_equal:'.now('America/Tegucigalpa')->toDateString()],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function customMessages(): array
    {
        return [
            'nombre.required' => 'El nombre completo o razón social es obligatorio.',
            'tipo_documento.required' => 'Selecciona si el documento es Identidad o RTN.',
            'rtn.required' => 'Ingresa el número de identidad o RTN.',
            'rtn.regex' => 'La identidad debe tener 13 dígitos y el RTN 14 dígitos, sin letras.',
            'rtn.unique' => 'El documento ya está registrado para otro cliente.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria para clientes con identidad.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
        ];
    }
}
