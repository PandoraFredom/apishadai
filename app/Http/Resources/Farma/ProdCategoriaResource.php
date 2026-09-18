<?php

namespace App\Http\Resources\Farma;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class ProdCategoriaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
        ];
    }
}
