<?php

namespace App\DTOs;

class MunicipioDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombre,
        public readonly int $departamento,
        public readonly ?\DateTime $created_at = null,
        public readonly ?\DateTime $updated_at = null,
    ) {}

    public static function fromModel($municipio): self
    {
        return new self(
            id: $municipio->id,
            nombre: $municipio->nombre,
            departamento: $municipio->departamento,
            created_at: $municipio->created_at,
            updated_at: $municipio->updated_at,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'departamento' => $this->departamento,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public static function fromRequest($request): self
    {
        return new self(
            id: $request->input('id') ?? null,
            nombre: $request->input('nombre'),
            departamento: $request->input('departamento'),
            created_at: $request->input('created_at') ?? null,
            updated_at: $request->input('updated_at') ?? null,
        );
    }
}
