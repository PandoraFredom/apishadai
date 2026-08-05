<?php

namespace App\Http\Resources\Farma;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductActiveResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto,
            'principio_activo_id' => $this->pactivo,
            'producto' => $this->whenLoaded('productoDetalle', fn (): array => [
                'id' => $this->productoDetalle->id,
                'codigo' => $this->productoDetalle->codigo,
                'codigobar' => $this->productoDetalle->codigobar,
                'descripcion' => $this->productoDetalle->descripcion,
            ]),
            'principio_activo' => ProductCatalogResource::make($this->whenLoaded('principioActivo')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
