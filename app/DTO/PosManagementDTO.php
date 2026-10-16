<?php

namespace App\DTO;

final readonly class PosManagementDTO
{
    public function __construct(public array $data) {}

    public static function fromRequest(array $data): self
    {
        if (isset($data['usuario'])) {
            $data['usuario'] = strtolower(trim($data['usuario']));
        }
        if (isset($data['nombre'])) {
            $data['nombre'] = trim(strip_tags($data['nombre']));
        }
        if (isset($data['vendedor'])) {
            $data['vendedor'] = trim(strip_tags($data['vendedor']));
        }
        foreach (['es_efectivo', 'requiere_referencia', 'controla_existencia', 'permite_venta', 'permite_precio_manual', 'incluye_isv'] as $field) {
            if (isset($data['datos'][$field])) {
                $data['datos'][$field] = (bool) $data['datos'][$field];
            }
        }
        if (isset($data['stock_id'])) {
            $data['stock_id'] = (int) $data['stock_id'];
        }

        return new self($data);
    }
}
