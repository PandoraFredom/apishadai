<?php

namespace App\DTOs;

class HorasLabDTO
{
    public function __construct(
        public readonly int $horasLab,
        public readonly int $horasLunch,
        public readonly ?int $usuario = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromCreateRequest(array $data): self
    {
        return new self(
            horasLab: (int) $data['horas_lab'],
            horasLunch: (int) $data['horas_lunch'],
            usuario: (int) $data['usuario']['id'],
        );
    }

    public static function fromUpdateRequest(array $data): self
    {
        return new self(
            horasLab: (int) $data['horas_lab'],
            horasLunch: (int) $data['horas_lunch'],
            id: (int) $data['id'],
        );
    }

    public function toCreateArray(): array
    {
        return [
            'usuario' => $this->usuario,
            'horas_lab' => $this->horasLab,
            'horas_lunch' => $this->horasLunch,
        ];
    }

    public function toUpdateArray(): array
    {
        return [
            'horas_lab' => $this->horasLab,
            'horas_lunch' => $this->horasLunch,
        ];
    }
}
