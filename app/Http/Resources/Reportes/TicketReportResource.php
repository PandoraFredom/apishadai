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
                'promocion' => $this->promocion,
                'cliente' => $this->cliente,
                'usuario' => $this->Usuario->nombre,
                'stock' => $this->Stock->descripcion,
                'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
