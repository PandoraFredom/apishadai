<?php

namespace App\DTOs;

use Illuminate\Support\Facades\Log;

class AccionesVistaDTO
{
    public function __construct(
        public readonly string $vista,
        public readonly string $codigo,
        public readonly string $nombre,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['vista']['id'],
            strtolower(trim($data['codigo'] ?? '')),
            $data['nombre'],
        );
    }
    public function toArray(): array
    {
        return [
            'vista' => $this->vista,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
        ];
    }
}
