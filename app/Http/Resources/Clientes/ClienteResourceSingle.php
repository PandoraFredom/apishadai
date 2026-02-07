<?php

namespace App\Http\Resources\Clientes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResourceSingle extends JsonResource
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
            'docid' => $this->docid,
            'pnombre' => $this->pnombre,
            'snombre' => $this->snombre,
            'papellido' => $this->papellido,
            'spaellido' => $this->spaellido,
            'edad' => 0,
            'telefono' => '',
            'genero' => '',
            'municipio' => 0,
            'departamento' => 0,
            'phone_updated_at' => null,
            'created_at' => null,
            'updated_at' => null,
        ];
    }

}
