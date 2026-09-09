<?php

namespace App\DTOs;

class UserDTO
{
    public function __construct(

        public readonly ?int $id,
        public readonly int $rol,
        public readonly string $nombre,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $password,
        public readonly int $estado
    ) {}

    /**
     * Crea DTO desde request (crear usuario)
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            id: null,
            rol: (int) ($data['rol']['id'] ?? 1),
            nombre: trim($data['nombre'] ?? ''),
            name: trim($data['name'] ?? ''),
            email: strtolower(trim($data['email'] ?? '')),
            password: $data['password'] ?? null,
            estado: (int) ($data['estado']['id'] ?? 1)
        );
    }

    //fromupdatedrequest
    public static function fromUpdateRequest(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            rol: (int) ($data['rol']['id'] ?? 1),
            nombre: trim($data['nombre'] ?? ''),
            name: trim($data['name'] ?? ''),
            email: strtolower(trim($data['email'] ?? '')),
            password: $data['password'] ?? null,
            estado: (int) ($data['estado']['id'] ?? 1)
        );
    }


    /**
     * Crea DTO desde modelo Eloquent
     * (password nunca se expone)
     */
    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            rol: (int) $model->rol,
            nombre: $model->nombre,
            name: $model->name,
            email: $model->email,
            password: null,
            estado: (int) $model->estado
        );
    }

    /**
     * Representación completa (sin password)
     */
    public function toArray(): array
    {
        return [
            'id'     => $this->id,
            'rol'    => $this->rol,
            'nombre' => $this->nombre,
            'name'   => $this->name,
            'password' => $this->password,
            'email'  => $this->email,
            'estado' => $this->estado,
        ];
    }
}
