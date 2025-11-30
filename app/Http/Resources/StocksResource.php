<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StocksResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return  [
            'id' => $this->id,
            'descripcion' => $this->descripcion,
            'telefono' => $this->telefono,
            'ubicacion' => $this->ubicacion,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
