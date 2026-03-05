<?php

namespace App\DTOs;

class StockEstadoDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $descripcion,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            id: null,
            descripcion: trim($data['descripcion'] ?? ''),
        );
    }

    public static function fromUpdateRequest(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            descripcion: trim($data['descripcion'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'descripcion' => $this->descripcion,
        ];
    }

    public function toUpdateArray(): array
    {
        return [
            'descripcion' => $this->descripcion,
        ];
    }
}
