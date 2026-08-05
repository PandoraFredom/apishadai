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
            'tiene_imagen' => (bool) ($this->tiene_imagen ?? false),
            'categoria_id' => $this->categoria,
            'laboratorio_id' => $this->laboratorio,
            'unidad_id' => $this->unidad,
            'familia_id' => $this->familia,
            'estado_id' => $this->estado,
            'categoria' => ProductCatalogResource::make($this->whenLoaded('categoriaDetalle')),
            'laboratorio' => $this->whenLoaded('laboratorioDetalle'),
            'unidad' => ProductCatalogResource::make($this->whenLoaded('unidadDetalle')),
            'familia' => ProductCatalogResource::make($this->whenLoaded('familiaDetalle')),
            'estado' => ProductCatalogResource::make($this->whenLoaded('estadoDetalle')),
            'principios_activos' => ProductCatalogResource::collection(
                $this->whenLoaded('principiosActivos'),
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
