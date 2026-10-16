<?php

namespace App\Http\Resources\Pos;

use App\Models\Pos\UsuarioPos;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosManagementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = array_intersect_key($this->resource->attributesToArray(), array_flip(['id', 'nombre', 'vendedor', 'stock_id', 'rol_id', 'nivel', 'tipo', 'codigo', 'datos', 'activo', 'created_at', 'updated_at']));
        if ($this->resource instanceof UsuarioPos) {
            $data['rol_nombre'] = $this->whenLoaded('rol', fn () => $this->resource->rol?->nombre);
            $data['nivel'] = $this->whenLoaded('rol', fn () => $this->resource->rol?->nivel);
        }

        return $data;
    }
}
