<?php

namespace App\Http\Requests\Headquarters;

use App\Http\Requests\BaseRequest;
use Illuminate\Support\Str;

class RegistrationRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('device_id'))) {
            $this->merge(['device_id' => strtolower(trim($this->input('device_id')))]);
        }
    }

    public function rules(): array
    {
        $usesUuid = is_string($this->input('device_id')) && Str::isUuid($this->input('device_id'));

        return [
            'name' => ['required', 'string', 'max:100', 'regex:/\A[^|\x00-\x1F\x7F]+\z/u'],
            'device_id' => $usesUuid
                ? ['required', 'string', 'uuid', 'exists:devices,hq_pairing_uuid']
                : ['required', 'integer', 'exists:devices,id'],
            'stock_id' => ['prohibited'],
        ];
    }

    protected function customMessages(): array
    {
        return [
            'device_id.exists' => 'El dispositivo no está emparejado. Guarda el UUID generado en HQ en la vista Dispositivos de apishadai.',
        ];
    }
}
