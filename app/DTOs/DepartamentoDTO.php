<?php

namespace App\DTOs;

class DepartamentoDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
    ) {}

    public static function fromModel($departamento): self
    {
        return new self(
            id: $departamento->id,
            nombre: $departamento->nombre,
            created_at: $departamento->created_at,
            updated_at: $departamento->updated_at,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public static function fromRequest($request): self
    {
        return new self(
            id: $request->input('id') ?? null,
            nombre: $request->input('nombre'),
            created_at: $request->input('created_at') ?? null,
            updated_at: $request->input('updated_at') ?? null,
        );
    }
}
