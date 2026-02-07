<?php

namespace App\DTOs;

use function is_array;

class PromosDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly string $descripcion,
        public readonly string $fecha_inicio,
        public readonly string $fecha_fin,
        public readonly int $estado
    ) {}



    public static function fromRequest(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            nombre: $data['nombre'],
            descripcion: $data['descripcion'],
            fecha_inicio: $data['fecha_inicio'],
            fecha_fin: $data['fecha_fin'],
            estado: $data['estado']['id'],
        );
    }

    public static function fromUpdateRequest(array $data): self
    {
        return new self(
            id: $data['id'],
            nombre: $data['nombre'],
            descripcion: $data['descripcion'],
            fecha_inicio: '',
            fecha_fin: '',
            estado: $data['estado']['id'],
        );
    }

    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'estado' => $this->estado,
        ];
    }

    //to update array only etado
    public function toUpdateArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
        ];
    }

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            nombre: $model->nombre,
            descripcion: $model->descripcion ?? '',
            fecha_inicio: $model->fecha_inicio ?? '',
            fecha_fin: $model->fecha_fin ?? '',
            estado: 0,
        );
    }

}
