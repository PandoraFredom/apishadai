<?php

namespace App\Http\Resources\Farma;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'lote_id' => (int) $this->lote,
            'cantidad' => (int) $this->cantidad,
            'subtotal' => (float) $this->subtotal,
            'isv' => (float) $this->isv,
            'total' => (float) $this->total,
            'lote' => $this->whenLoaded('loteDetalle', fn (): array => [
                'id' => (int) $this->loteDetalle->id,
                'codigo' => (string) $this->loteDetalle->lote,
                'cantidad_disponible' => (int) $this->loteDetalle->cantidad,
                'fecha_expiracion' => $this->loteDetalle->fecha_exp?->toDateString(),
                'costo' => $this->loteDetalle->costo !== null ? (float) $this->loteDetalle->costo : null,
                'producto' => [
                    'id' => (int) $this->loteDetalle->producto,
                    'codigo' => (string) ($this->loteDetalle->productoDetalle?->codigo ?? ''),
                    'descripcion' => (string) ($this->loteDetalle->productoDetalle?->descripcion ?? ''),
                ],
            ]),
        ];
    }
}
