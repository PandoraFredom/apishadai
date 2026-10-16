<?php

namespace App\DTO;

class HeadquartersRegistrationDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $ip,
        public readonly int|string $deviceId,
    ) {}
}
