<?php

namespace App\DTOs;

class VistaDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $modulo,
        public readonly string $nombre,
        public readonly int $estado,
        public readonly string $codigo
    ) {}

    /**
     * Crea DTO desde request (sin ID)
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? null),
            modulo: (int) ($data['modulo']['id'] ?? 0),
            nombre: trim($data['nombre'] ?? ''),
            estado: (int) ($data['estado']['id'] ?? 1),
            codigo: strtolower(trim($data['codigo'] ?? ''))
        );
    }

    /**
     * Crea DTO desde modelo Eloquent
     */
    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            modulo: (int) $model->modulo,
            nombre: $model->nombre,
            estado: (int) $model->estado,
            codigo: $model->codigo
        );
    }

    /**
     * Datos para actualizar en BD (solo campos modificables)
     */
    public function toUpdateArray(): array
    {
        return array_filter([
            'estado' => $this->estado,
        ]);
    }

    /**
     * Representación completa
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'modulo' => $this->modulo,
            'nombre' => $this->nombre,
            'estado' => $this->estado,
            'codigo' => $this->codigo,
        ];
    }

    /**
     * Crea DTO desde request para actualizar
     */
    public static function fromUpdateRequest(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            modulo: isset($data['modulo']) ? (int) $data['modulo'] : 0,
            nombre: trim($data['nombre'] ?? ''),
            estado: isset($data['estado']) ? (int) $data['estado']['id'] : 1,
            codigo: strtoupper(trim($data['codigo'] ?? ''))
        );
    }
}
