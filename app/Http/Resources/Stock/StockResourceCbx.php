<?php

namespace App\Http\Resources\Stock;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResourceCbx extends JsonResource
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
            'descripcion' => $this->descripcion,
            'telefono' => '',
            'ubicacion' => '',
            'created_at' => null,
            'updated_at' => null
        ];
    }
}
