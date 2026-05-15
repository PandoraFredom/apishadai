<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermisosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'modulo' =>[
                'nombre' => $this->Modulo->nombre,
            ],
            'vista' => [
                'nombre' => $this->Vista->nombre,
            ],
            'actionvista' => [
                'nombre' => $this->Actionvista->nombre,
            ],
            'tipo_tiempo' => [
                'nombre' => $this->TipoTiempo->nombre,
            ],
        ];
    }
}
