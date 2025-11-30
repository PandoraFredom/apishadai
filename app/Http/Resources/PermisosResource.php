<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermisosResource extends JsonResource
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
            'usuario' => $this->Usuario,
            'modulo' => $this->Modulo,
            'vista' => $this->Vista,
            'actionvista' => $this->Actionvista,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
