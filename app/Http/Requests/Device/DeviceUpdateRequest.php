<?php

namespace App\Http\Requests\Device;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class DeviceUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:devices,id',
            'estado.id' => 'required|integer|exists:device_estado,id',
        ];
    }

    protected function customMessages(): array
    {
        return [
            'estado.id.required' => 'El campo estado.id es obligatorio.',
            'estado.id.integer' => 'El campo estado.id debe ser un número entero.',
            'estado.id.exists' => 'El estado.id proporcionado no existe en la base de datos.',
        ];
    }
}
