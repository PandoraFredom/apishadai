<?php

namespace App\DTO;

class DeviceDTO
{
    public function __construct(
        public readonly string $ip,
        public readonly string $ip2,
        public readonly string $displayname,
        public readonly string $name,
        public readonly int $stock,
        public readonly int $estado,
        public readonly ?int $id = null,
        public readonly ?string $hqPairingUuid = null,
        public readonly bool $includesHqPairingUuid = false,
    ) {}

    /**
     * Crea un DTO al crear un nuevo dispositivo
     * Hashea ip, ip2 y name para almacenamiento seguro
     */
    public static function onCreate(array $data): self
    {
        return new self(
            ip: $data['ip'],
            ip2: $data['ip2'],
            displayname: $data['displayname'],
            name: $data['name'],
            stock: $data['stock']['id'],
            estado: $data['estado']['id'],
            id: $data['id'] ?? null,
            hqPairingUuid: $data['hq_pairing_uuid'] ?? null,
            includesHqPairingUuid: array_key_exists('hq_pairing_uuid', $data),
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
            id: $id,
            hqPairingUuid: $data['hq_pairing_uuid'] ?? null,
            includesHqPairingUuid: array_key_exists('hq_pairing_uuid', $data),
        );
    }

    /**
     * Convierte el DTO a array, filtrando valores vacíos
     */
    public function toArray(): array
    {
        $attributes = array_filter([
            'ip' => $this->ip ?: null,
            'ip2' => $this->ip2 ?: null,
            'displayname' => $this->displayname ?: null,
            'name' => $this->name ?: null,
            'stock' => $this->stock > 0 ? $this->stock : null,
            'estado' => $this->estado > 0 ? $this->estado : null,
        ]);
        if ($this->includesHqPairingUuid) {
            $attributes['hq_pairing_uuid'] = $this->hqPairingUuid;
        }

        return $attributes;
    }

    public function toUpdateArray(): array
    {
        $attributes = array_filter([
            'id' => $this->id > 0 ? $this->id : null,
            'estado' => $this->estado > 0 ? $this->estado : null,
        ]);
        if ($this->includesHqPairingUuid) {
            $attributes['hq_pairing_uuid'] = $this->hqPairingUuid;
        }

        return $attributes;
    }
}
