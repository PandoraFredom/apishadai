<?php

namespace App\DTOs;

class StockDTO
{
    public function __construct(
        public readonly string $descripcion,
        public readonly string $telefono,
        public readonly string $ubicacion,
        public readonly ?int $id = null,
    ) {}

    // from request
    public static function fromRequest($request): self
    {
        return new self(
            descripcion: $request->input('descripcion', ''),
            telefono: $request->input('telefono', ''),
            ubicacion: $request->input('ubicacion', ''),
            id: $request->input('id', null),
        );
    }


    public static function fromArray(array $data): self
    {
        return new self(
            descripcion: $data['descripcion'] ?? '',
            telefono: $data['telefono'] ?? '',
            ubicacion: $data['ubicacion'] ?? '',
            id: $data['id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'descripcion' => $this->descripcion,
            'telefono' => $this->telefono,
            'ubicacion' => $this->ubicacion,
        ];
    }
}
