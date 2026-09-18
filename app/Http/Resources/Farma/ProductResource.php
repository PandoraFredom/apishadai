<?php

namespace App\Http\Resources\Farma;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'codigobar' => $this->codigobar,
            'descripcion' => $this->descripcion,
            'categoria' => $this->whenLoaded('categoriaDetalle', fn ($categoria): array => $categoria->only(['id', 'nombre'])),
            'laboratorio' => $this->whenLoaded('laboratorioDetalle', fn ($laboratorio): array => $laboratorio->only(['id', 'nombre'])),
            'unidad' => $this->whenLoaded('unidadDetalle', fn ($unidad): array => $unidad->only(['id', 'cantidad_c','cantidad_v'])),
            'familia' => $this->whenLoaded('Familia', fn ($familia): array => [
                'id' => $familia->id,
                'descripcion' => $familia->descripcion,
            ]),
            'estado' => $this->whenLoaded('estadoDetalle', fn ($estado): array => $estado->only(['id', 'descripcion'])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
