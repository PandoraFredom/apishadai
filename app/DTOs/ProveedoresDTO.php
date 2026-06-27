<?php

namespace App\DTOs;

class ProveedoresDTO
{

    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $nombre = null,
        public readonly ?string $telefono = null,
        public readonly ?string $direccion = null,
        public readonly ?string $papellido = null



    ) {}
}
