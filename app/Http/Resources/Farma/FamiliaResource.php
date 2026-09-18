<?php

namespace App\Http\Resources\Farma;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class FamiliaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'descripcion' => $this->descripcion,
            'presentacion' => [
                'id' => $this->Presentacion->id,
                'descripcion' => $this->Presentacion->descripcion,
            ],
            'administracion' => [
                'id' => $this->Administracion->id,
                'descripcion' => $this->Administracion->descripcion,
            ],
        ];
    }
}
