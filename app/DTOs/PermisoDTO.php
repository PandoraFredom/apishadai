<?php

namespace App\DTOs;

class PermisoDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $usuario,
        public readonly int $modulo,
        public readonly int $vista,
        public readonly int $actionvista,
        public readonly int $tipo_tiempo,
        public readonly ?int $lifetime
    ) {}

    /**
     * Crea DTO desde request (crear permiso)
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            id: null,
            usuario: (int) ($data['usuario']['id'] ?? 0),
            modulo: (int) ($data['modulo']['id'] ?? 0),
            vista: (int) ($data['vista']['id'] ?? 0),
            actionvista: (int) ($data['actionvista']['id'] ?? 0),
            tipo_tiempo: (int) ($data['tipo_tiempo']['id'] ?? 0),
            lifetime: isset($data['lifetime']) ? (int) $data['lifetime'] : null
        );
    }

    /**
     * Crea DTO desde modelo Eloquent
     */
    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            usuario: (int) $model->usuario,
            modulo: (int) $model->modulo,
            vista: (int) $model->vista,
            actionvista: (int) $model->actionvista,
            tipo_tiempo: (int) $model->tipo_tiempo,
            lifetime: $model->lifetime !== null ? (int) $model->lifetime : null
        );
    }

    /**
     * Representación completa
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'usuario'      => $this->usuario,
            'modulo'       => $this->modulo,
            'vista'        => $this->vista,
            'actionvista'  => $this->actionvista,
            'tipo_tiempo'  => $this->tipo_tiempo,
            'lifetime'     => $this->lifetime,
        ];
    }
}
