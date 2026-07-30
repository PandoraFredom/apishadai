<?php

namespace App\Http\Resources\Reportes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketReportResource extends JsonResource
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
            'ntiket' => $this->ntiket,
            'promocion' => [
                'id' => $this->Promocion?->id,
                'nombre' => $this->Promocion?->nombre,
            ],
            'cliente' => [
                'id' => $this->Cliente?->id,
                'docid' => $this->Cliente?->docid,
                'pnombre' => $this->Cliente?->pnombre,
                'snombre' => $this->Cliente?->snombre,
                'papellido' => $this->Cliente?->papellido,
                'spaellido' => $this->Cliente?->spaellido,
            ],
            'usuario' => [
                'id' => $this->Usuario?->id,
                'nombre' => $this->Usuario?->nombre,
            ],
            'stock' => [
                'id' => $this->Stock?->id,
                'descripcion' => $this->Stock?->descripcion,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
