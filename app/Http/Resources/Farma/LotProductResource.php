<?php

namespace App\Http\Resources\Farma;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'producto_id' => (int) $this->producto,
            'codigo' => (string) ($this->productoDetalle?->codigo ?? ''),
            'descripcion' => (string) ($this->productoDetalle?->descripcion ?? ''),
            'laboratorio' => (string) ($this->productoDetalle?->laboratorioDetalle?->nombre ?? ''),
            'presentacion' => (string) ($this->productoDetalle?->familiaDetalle?->presentacionDetalle?->descripcion ?? ''),
            'administracion' => (string) ($this->productoDetalle?->familiaDetalle?->administracionDetalle?->descripcion ?? ''),
            'tiene_imagen' => (bool) ($this->productoDetalle?->tiene_imagen ?? false),
            'lotes_count' => (int) $this->lotes_count,
            'cantidad_total' => (int) $this->cantidad_total,
            'proxima_expiracion' => $this->proxima_expiracion,
        ];
    }
}
