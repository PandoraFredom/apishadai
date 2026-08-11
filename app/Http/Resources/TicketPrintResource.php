<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketPrintResource extends JsonResource
{

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'numero' =>  $this->ntiket,
            'valor' =>  $this->valor,
            'fecha' => $this->created_at?->toIso8601String(),
            'promocion' => $this->Promocion->nombre,
            'cliente_n' => trim($this->Cliente->pnombre . ' ' . $this->Cliente->papellido),
            'cliente_i' =>  $this->Cliente->docid,
            'cliente_t' =>  $this->Cliente->telefono,
            'stock' =>  $this->Stock->descripcion,
        ];
    }
}
