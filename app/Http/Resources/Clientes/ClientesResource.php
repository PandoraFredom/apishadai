<?php

namespace App\Http\Resources\Clientes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientesResource extends JsonResource
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
            'edad' => $this->edad,
            'telefono' => $this->telefono,
            'genero' => $this->genero,
            'municipio' => $this->Municipio,
            'departamento' => $this->Departamento,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
