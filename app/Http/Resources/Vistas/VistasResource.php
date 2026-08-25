<?php

namespace App\Http\Resources\Vistas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VistasResource extends JsonResource
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
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'modulo' => [
                'id' => $this->Modulo->id,
                'nombre' => $this->Modulo->nombre,
            ],
            'estado' => [
                'id' => $this->Estado->id,
                'descripcion' => $this->Estado->descripcion,
            ],

        ];
    }
}
