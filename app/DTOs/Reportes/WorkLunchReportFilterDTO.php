<?php

namespace App\DTOs\Reportes;

class WorkLunchReportFilterDTO
{
    public function __construct(
        public readonly int $usuario,
        public readonly string $desde,
        public readonly string $hasta,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            usuario: (int) $data['usuario'],
            desde: $data['work_date'][0],
            hasta: $data['work_date'][1],
        );
    }
}
