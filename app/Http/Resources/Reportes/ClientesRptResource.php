<?php

namespace App\Http\Resources\Reportes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientesRptResource extends JsonResource
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
            'pnombre' => $this->pnombre.' '.$this->snombre,
            'papellido' => $this->papellido.' '.$this->spaellido,
        ];
    }
}
