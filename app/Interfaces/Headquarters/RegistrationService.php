<?php

namespace App\Interfaces\Headquarters;

use App\DTO\HeadquartersRegistrationDTO;

interface RegistrationService
{
    public function requestRegistration(HeadquartersRegistrationDTO $data): array;

    public function confirmRegistration(array $data, string $ip): array;
}
