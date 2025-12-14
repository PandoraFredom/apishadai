<?php

namespace App\Http\Resources\Vistas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VistasResourceCbx extends JsonResource
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
            'modulo' => null,
            'nombre' => $this->nombre,
            'codigo' => '',
            'estado' => null,
            'created_at' => '',
            'updated_at' => ''
        ];
    }
}
