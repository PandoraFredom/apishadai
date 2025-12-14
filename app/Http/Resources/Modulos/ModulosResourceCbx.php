<?php

namespace App\Http\Resources\Modulos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModulosResourceCbx extends JsonResource
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
            'codigo' => '',
            'estado' => null,
            'created_at' => null,
            'updated_at' => null
        ];
    }
}
