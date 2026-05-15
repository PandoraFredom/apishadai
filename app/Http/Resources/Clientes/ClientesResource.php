<?php

namespace App\Http\Resources\Clientes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use function array_key_exists;
use function is_int;

class ClientesResource extends JsonResource
{


    public function toArray(Request $request): array
    {
        return   [
            'id' => $this->id,
            'docid' => $this->docid,
            'pnombre' => $this->pnombre,
            'snombre' => $this->snombre,
            'papellido' => $this->papellido,
            'spaellido' => $this->spaellido,
            'edad' => $this->edad,
            'telefono' => $this->telefono,
            'genero' => $this->genero,
            'Municipio' => $this->Municipio,
            'Departamento' => $this->Departamento,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
