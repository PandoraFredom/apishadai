<?php

namespace App\Http\Resources\Farma;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'lote_id' => (int) $this->lote,
            'precio' => (float) $this->precio,
            'isv' => (bool) $this->isv,
            'dto1' => (float) $this->dto1,
            'dto2' => (float) $this->dto2,
            'dto3' => (float) $this->dto3,
            'dto4' => (float) $this->dto4,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
