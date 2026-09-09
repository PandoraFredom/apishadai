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
        public readonly int $estado,
        public readonly int $impresiones,
        public readonly float $valor
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
            impresiones: (int) ($data['impresiones'] ?? 0),
            valor: (float) ($data['valor'] ?? 0),
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
            impresiones: (int) ($data['impresiones'] ?? 0),
            valor: (float) ($data['valor'] ?? 0),
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
            'impresiones' => $this->impresiones,
            'valor' => $this->valor,
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
            'impresiones' => $this->impresiones,
            'valor' => $this->valor,
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
            impresiones: (int) ($model->impresiones ?? 0),
            valor: (float) ($model->valor ?? 0),
        );
    }

}
