<?php

namespace App\Http\Resources\Farma;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'compra_id' => $this->compra !== null ? (int) $this->compra : null,
            'producto_id' => (int) $this->producto,
            'lote' => (string) $this->lote,
            'fecha_elaboracion' => $this->fecha_elab?->toDateString(),
            'fecha_expiracion' => $this->fecha_exp?->toDateString(),
            'cantidad' => (int) $this->cantidad,
            'costo' => $this->costo !== null ? (float) $this->costo : null,
            'isv' => $this->isv !== null ? (bool) $this->isv : null,
            'factura' => $this->whenLoaded('compraDetalle', fn (): ?array => $this->compraDetalle === null ? null : [
                'id' => (int) $this->compraDetalle->id,
                'nro' => (string) $this->compraDetalle->nro,
            ]),
            'producto' => $this->whenLoaded('productoDetalle', fn (): array => [
                'id' => (int) $this->productoDetalle->id,
                'codigo' => (string) $this->productoDetalle->codigo,
                'descripcion' => (string) $this->productoDetalle->descripcion,
                'laboratorio' => (string) ($this->productoDetalle->laboratorioDetalle?->nombre ?? ''),
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
