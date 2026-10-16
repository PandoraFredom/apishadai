<?php

namespace App\Http\Requests\Pos;

use App\Models\Pos\Clientepos;
use Illuminate\Validation\Rule;

class ClienteposUpdateRequest extends ClienteposRequest
{
    protected function prepareForValidation(): void
    {
        $original = $this->input('rtn');
        parent::prepareForValidation();
        $client = Clientepos::query()->find((int) $this->route('id'));
        if ($client !== null && $client->tipo_documento === null && $this->input('tipo_documento') === null && $original === $client->rtn) {
            $this->merge(['rtn' => $client->rtn]);
        }
        // Old callers may update contact data without removing document metadata.
        if ($client !== null && $this->input('rtn') === $client->rtn) {
            if (! $this->exists('tipo_documento')) {
                $this->merge(['tipo_documento' => $client->tipo_documento]);
            }
            if (! $this->exists('fecha_nacimiento')) {
                $this->merge(['fecha_nacimiento' => $client->fecha_nacimiento?->toDateString()]);
            }
        }
        $this->merge(['id' => $this->route('id')]);
    }

    protected function allowsLegacyDocument(): bool
    {
        $client = Clientepos::query()->find((int) $this->route('id'));

        return $client !== null && $client->tipo_documento === null && $this->input('tipo_documento') === null
            && $this->input('rtn') === $client->rtn;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        array_pop($rules['rtn']);
        $rules['rtn'][] = Rule::unique('clientepos', 'rtn')->ignore((int) $this->route('id'));

        return [...$rules, 'id' => ['required', 'integer', 'min:1', 'exists:clientepos,id']];
    }
}
