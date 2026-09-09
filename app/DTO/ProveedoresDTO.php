<?php

namespace App\DTOs;

class ProveedoresDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly string $telefono,
        public readonly string $direccion,
        public readonly ?string $imagen,
    ) {}

    public static function onCreate(array $data): self
    {
        return new self(
            id: null,
            nombre: trim($data['nombre'] ?? ''),
            telefono: trim($data['telefono'] ?? ''),
            direccion: trim($data['direccion'] ?? ''),
            imagen: isset($data['imagen']) ? trim((string) $data['imagen']) : null,
        );
    }

    public static function fromUpdateRequest(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            nombre: trim($data['nombre'] ?? ''),
            telefono: trim($data['telefono'] ?? ''),
            direccion: trim($data['direccion'] ?? ''),
            imagen: isset($data['imagen']) ? trim((string) $data['imagen']) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'imagen' => $this->imagen,
        ], fn($value) => $value !== null);
    }

    public function toUpdateArray(): array
    {
        return $this->toArray();
    }
}
