<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HorasLabResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'usuario' => [
                'id' => $this->User?->id,
                'nombre' => $this->User?->nombre,
            ],
            'horas_lab' => $this->horas_lab,
            'horas_lunch' => $this->horas_lunch,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
