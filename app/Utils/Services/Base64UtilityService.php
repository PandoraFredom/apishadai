<?php

namespace App\Utils\Services;

interface Base64UtilityService
{
    public function sanitize(string $data): ?string;
}
