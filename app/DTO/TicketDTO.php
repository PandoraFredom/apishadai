<?php

namespace App\DTOs;

class TicketDTO
{
    public function __construct(
        public int $promocion,
        public int $cliente,
        public string $ntiket,
        public int $usuario,
        public int $stock,
        public ?int $id = null,
    ) {}

    /**
     * Crea DTO desde request (sin ID)
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            promocion: (int) ($data['promocion']['id']),
            cliente: (int) ($data['cliente']['id']),
            ntiket: trim((string) ($data['ntiket'] ?? '')),
            usuario: (int) ($data['usuario']['id'] ?? 0),
            stock: (int) ($data['stock']['id']),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'promocion' => $this->promocion,
            'cliente' => $this->cliente,
            'ntiket' => $this->ntiket,
            'usuario' => $this->usuario,
            'stock' => $this->stock,
        ];
    }
}
