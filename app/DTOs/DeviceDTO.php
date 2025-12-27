<?php

namespace App\DTOs;

use App\Services\EncryptionService;

class DeviceDTO
{
    private static ?EncryptionService $encService = null;

    public function __construct(
        public readonly string $ip,
        public readonly string $ip2,
        public readonly string $displayname,
        public readonly string $name,
        public readonly int $stock,
        public readonly int $estado,
        public readonly ?int $id = null
    ) {
        // Inicializar EncryptionService si no está set
        if (self::$encService === null) {
            self::$encService = app(EncryptionService::class);
        }
    }

    /**
     * Crea un DTO al crear un nuevo dispositivo
     * Hashea ip, ip2 y name para almacenamiento seguro
     */
    public static function onCreate(array $data): self
    {
        $encService = app(EncryptionService::class);

        return new self(
            ip: $encService->genHash($data['ip']),
            ip2: $encService->genHash($data['ip2']),
            displayname: $data['displayname'],
            name: $encService->genHash($data['name']),
            stock: $data['stock']['id'],
            estado: $data['estado']['id'],
            id: $data['id'] ?? null
        );
    }
    public static function fromUpdateRequest(array $data, int $id): self
    {
        return new self(
            ip: '',
            ip2: '',
            displayname: '',
            name: '',
            stock: 0,
            estado: $data['estado']['id'],
            id: $id
        );
    }






    /**
     * Convierte el DTO a array, filtrando valores vacíos
     */
    public function toArray(): array
    {
        return array_filter([
            'ip' => $this->ip ?: null,
            'ip2' => $this->ip2 ?: null,
            'displayname' => $this->displayname ?: null,
            'name' => $this->name ?: null,
            'stock' => $this->stock > 0 ? $this->stock : null,
            'estado' => $this->estado > 0 ? $this->estado : null,
        ]);
    }

    public function toUpdateArray(): array
    {
        return array_filter([
            'id' => $this->id > 0 ? $this->id : null,
            'estado' => $this->estado > 0 ? $this->estado : null,
        ]);
    }
}
