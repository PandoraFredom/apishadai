<?php

namespace App\Http\Requests\Device;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class DeviceUpdateRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('hq_pairing_uuid'))) {
            $this->merge(['hq_pairing_uuid' => strtolower(trim($this->input('hq_pairing_uuid'))) ?: null]);
        }
    }

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
            'hq_pairing_uuid' => ['sometimes', 'nullable', 'uuid', 'not_in:00000000-0000-0000-0000-000000000000',
                Rule::unique('devices', 'hq_pairing_uuid')->ignore($this->route('id'))],
        ];
    }

    protected function customMessages(): array
    {
        return [
            'hq_pairing_uuid.uuid' => 'Pega un UUID de emparejamiento válido generado en HQ.',
            'hq_pairing_uuid.unique' => 'Este UUID de HQ ya está vinculado a otro dispositivo.',
            'estado.id.required' => 'El campo estado.id es obligatorio.',
            'estado.id.integer' => 'El campo estado.id debe ser un número entero.',
            'estado.id.exists' => 'El estado.id proporcionado no existe en la base de datos.',
        ];
    }
}
