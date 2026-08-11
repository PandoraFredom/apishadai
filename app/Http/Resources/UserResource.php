<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'rol' => [
                'id' => $this->Rol->id,
                'descripcion' => $this->Rol->descripcion,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'estado' => [
                'id' => $this->Estado->id,
                'descripcion' => $this->Estado->descripcion,
            ],
        ];
    }
}
