<?php

namespace App\DTOs;

class LaboratorioDTO
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
        return self::fromData($data);
    }

    public static function fromUpdateRequest(array $data): self
    {
        return self::fromData($data, (int) ($data['id'] ?? 0));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'imagen' => $this->imagen,
        ], fn (mixed $value): bool => $value !== null);
    }

    /** @param array<string, mixed> $data */
    private static function fromData(array $data, ?int $id = null): self
    {
        return new self(
            id: $id,
            nombre: trim((string) ($data['nombre'] ?? '')),
            telefono: trim((string) ($data['telefono'] ?? '')),
            direccion: trim((string) ($data['direccion'] ?? '')),
            imagen: isset($data['imagen']) ? trim((string) $data['imagen']) : null,
        );
    }
}
