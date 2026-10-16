<?php

namespace App\Http\Resources\Device;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hq_client_uuid' => $this->hq_client_uuid,
            'hq_pairing_uuid' => $this->hq_pairing_uuid,
            'displayname' => $this->displayname,
            'stock' => [
                'id' => $this->stock,
                'descripcion' => $this->Stock->descripcion,
            ],
            'estado' => [
                'id' => $this->estado,
                'descripcion' => $this->Estado->descripcion,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
