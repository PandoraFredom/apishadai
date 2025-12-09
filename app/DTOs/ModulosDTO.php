<?php

namespace App\DTOs;

class ModulosDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $nombre,
        public readonly string $codigo,
        public readonly int $estado
    ) {
    }

    /**
     * Crea DTO desde request (sin ID)
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            id: null,
            nombre: trim($data['nombre'] ?? ''),
            codigo: strtoupper(trim($data['codigo'] ?? '')),
            estado: (int) ($data['estado'] ?? 1)
        );
    }

    /**
     * Crea DTO desde modelo Eloquent
     */
    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            nombre: $model->nombre,
            codigo: $model->codigo,
            estado: $model->estado
        );
    }

    /**
     * Datos para crear en BD (sin ID)
     */
    public function toCreateArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'estado' => $this->estado,
        ];
    }

    /**
     * Datos para actualizar en BD (solo campos modificables)
     */
    public function toUpdateArray(): array
    {
        return array_filter([
            'nombre' => null,
            'codigo' => null,
            'estado' => $this->estado,
        ], fn($value) => $value !== null);
    }

    /**
     * Representación completa
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'estado' => $this->estado,
        ];
    }

    public static function fromUpdateRequest(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            nombre: trim($data['nombre'] ?? ''),
            codigo: strtoupper(trim($data['codigo'] ?? '')),
            estado: isset($data['estado']) ? (int) $data['estado']['id'] : 1
        );
    }
}