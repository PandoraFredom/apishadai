<?php

namespace App\DTOs;

class MatchTokenDTO
{
    public function __construct(
        public ?int $id = null,
        public int $usuario = 0,
        public int $device = 0,
        public string $token = ''
    ) {}

    public static function onCreate(array $request): self
    {
        return new self(
            id: null,
            usuario: (int) $request['usuario'] ?? 0,
            device: (int) $request['device'] ?? 0,
            token: $request['token'] ?? ''
        );
    }

    public static function fromUpdateRequest(array $request): self
    {
        return new self(
            id: (int) $request['id'] ?? null,
            usuario: (int) $request['usuario'] ?? 0,
            device: (int) $request['device'] ?? 0,
            token: $request['token'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'usuario' => $this->usuario,
            'device' => $this->device,
            'token' => $this->token,
        ];
    }

    public function toUpdateArray(): array
    {
        return [
            'usuario' => $this->usuario,
            'device' => $this->device,
            'token' => $this->token,
        ];
    }
}
