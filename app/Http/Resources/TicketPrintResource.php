<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketPrintResource extends JsonResource
{
    public const TEMPLATE_VERSION = 1;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'numero' => (int) $this->ntiket,
            'valor' => (float) $this->valor,
            'fecha' => $this->created_at?->toIso8601String(),
            'template_version' => self::TEMPLATE_VERSION,
            'promocion' => [
                'id' => (int) $this->Promocion->id,
                'nombre' => (string) $this->Promocion->nombre,
            ],
            'cliente' => [
                'id' => (int) $this->Cliente->id,
                'nombre' => trim($this->Cliente->pnombre.' '.$this->Cliente->papellido),
                'identidad' => (string) $this->Cliente->docid,
                'telefono' => (string) $this->Cliente->telefono,
            ],
            'stock' => [
                'id' => (int) $this->Stock->id,
                'descripcion' => (string) $this->Stock->descripcion,
            ],
        ];
    }
}
