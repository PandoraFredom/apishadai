<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActionsVistasResource extends JsonResource
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
            'vista' => null,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'created_at' => null,
            'updated_at' => null,
        ];
    }
}
