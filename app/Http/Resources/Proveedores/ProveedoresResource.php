<?php

namespace App\Http\Resources\Proveedores;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedoresResource extends JsonResource
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
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'tiene_imagen' => (bool) ($this->tiene_imagen ?? $this->imagen),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
