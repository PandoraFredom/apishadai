<?php

namespace App\DTO;

class ClienteposDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly ?string $rtn,
        public readonly ?string $telefono,
        public readonly ?string $direccion,
        public readonly ?string $tipo_documento = null,
        public readonly ?string $fecha_nacimiento = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        $data = self::normalize($data);

        return new self($data['nombre'], $data['rtn'] ?? null, $data['telefono'] ?? null, $data['direccion'] ?? null, $data['tipo_documento'] ?? null, $data['fecha_nacimiento'] ?? null);
    }

    public function toArray(): array
    {
        return ['nombre' => $this->nombre, 'rtn' => $this->rtn, 'telefono' => $this->telefono, 'direccion' => $this->direccion, 'tipo_documento' => $this->tipo_documento, 'fecha_nacimiento' => $this->fecha_nacimiento];
    }

    public static function normalize(array $data): array
    {
        foreach (['nombre', 'rtn', 'telefono', 'direccion', 'tipo_documento', 'fecha_nacimiento'] as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field])) {
                continue;
            }

            $value = trim(strip_tags($data[$field]));
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
            if ($field === 'rtn') {
                $value = strtoupper(preg_replace('/[\s-]+/u', '', $value) ?? '');
            }
            if ($field === 'tipo_documento') {
                $value = strtoupper($value);
            }
            $data[$field] = $field !== 'nombre' && $value === '' ? null : $value;
        }

        return $data;
    }
}
