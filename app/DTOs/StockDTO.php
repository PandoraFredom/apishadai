<?php

namespace App\DTOs;

class StockDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $descripcion,
        public readonly string $telefono,
        public readonly string $ubicacion,
        public readonly int $estado,
    ) {}

    public static function onCreate(array $data): self
    {
        return new self(
            id: null,
            descripcion: trim($data['descripcion'] ?? ''),
            telefono: trim($data['telefono'] ?? ''),
            ubicacion: trim($data['ubicacion'] ?? ''),
            estado: (int) ($data['estado']['id'] ?? 0),
        );
    }

    public static function fromUpdateRequest(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            descripcion: trim($data['descripcion'] ?? ''),
            telefono: trim($data['telefono'] ?? ''),
            ubicacion: trim($data['ubicacion'] ?? ''),
            estado: (int) ($data['estado']['id'] ?? 0),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            descripcion: $data['descripcion'] ?? '',
            telefono: $data['telefono'] ?? '',
            ubicacion: $data['ubicacion'] ?? '',
            estado: (int) ($data['estado'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'descripcion' => $this->descripcion,
            'telefono' => $this->telefono,
            'ubicacion' => $this->ubicacion,
            'estado' => $this->estado > 0 ? $this->estado : null,
        ], fn($value) => $value !== null);
    }

    public function toUpdateArray(): array
    {
        return array_filter([
            'descripcion' => $this->descripcion,
            'telefono' => $this->telefono,
            'ubicacion' => $this->ubicacion,
            'estado' => $this->estado > 0 ? $this->estado : null,
        ], fn($value) => $value !== null);
    }
}
