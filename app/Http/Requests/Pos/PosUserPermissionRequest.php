<?php

namespace App\Http\Requests\Pos;

use App\Http\Requests\BaseRequest;

class PosUserPermissionRequest extends BaseRequest
{
    public function rules(): array
    {
        return ['usuario' => ['required', 'array:id'], 'usuario.id' => ['required', 'integer', 'exists:usuariospos,id'],
            'modulo' => ['required', 'array:id'], 'modulo.id' => ['required', 'integer', 'exists:modulospos,id'],
            'vista' => ['required', 'array:id'], 'vista.id' => ['required', 'integer', 'exists:vistaspos,id'],
            'actionvista' => ['required', 'array:id'], 'actionvista.id' => ['required', 'integer', 'exists:botonespos,id'],
            'tipo_tiempo' => ['required', 'array:id'], 'tipo_tiempo.id' => ['required', 'integer', 'exists:tipos_tiempo,id']];
    }
}
