<?php

namespace App\Http\Resources\Farma;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $lot = $this->relationLoaded('loteDetalle') ? $this->loteDetalle : null;
        $product = $lot?->relationLoaded('productoDetalle') ? $lot->productoDetalle : null;
        $laboratory = $product?->relationLoaded('laboratorioDetalle') ? $product->laboratorioDetalle : null;
        $category = $product?->relationLoaded('categoriaDetalle') ? $product->categoriaDetalle : null;
        $unit = $product?->relationLoaded('unidadDetalle') ? $product->unidadDetalle : null;
        $family = $product?->relationLoaded('Familia') ? $product->Familia : null;
        $purchase = $lot?->relationLoaded('compraDetalle') ? $lot->compraDetalle : null;

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
                'fecha_elaboracion' => $this->loteDetalle->fecha_elab?->toDateString(),
                'factura' => $purchase?->nro,
                'costo' => $this->loteDetalle->costo !== null ? (float) $this->loteDetalle->costo : null,
                'producto' => [
                    'id' => (int) $this->loteDetalle->producto,
                    'codigo' => (string) ($this->loteDetalle->productoDetalle?->codigo ?? ''),
                    'descripcion' => (string) ($this->loteDetalle->productoDetalle?->descripcion ?? ''),
                    'codigobar' => $product?->codigobar,
                    'laboratorio' => $laboratory?->nombre,
                    'categoria' => $category?->nombre,
                    'unidad' => $unit !== null ? trim($unit->abreviatura_v.' ('.$unit->cantidad_v.')') : null,
                    'familia' => $family?->descripcion,
                    'presentacion' => $family?->Presentacion?->descripcion,
                    'administracion' => $family?->Administracion?->descripcion,
                ],
            ]),
        ];
    }
}
