<?php

namespace App\Http\Resources\Pos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteposResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'rtn' => $this->rtn,
            'tipo_documento' => $this->tipo_documento,
            'fecha_nacimiento' => $this->fecha_nacimiento?->toDateString(),
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
