<?php

namespace App\Http\Resources\Ubicacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MunicipiosResourceSingle extends JsonResource
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
            'departamento' => $this->departamento,
            'created_at' => null,
            'updated_at' => null,
        ];
    }
}
