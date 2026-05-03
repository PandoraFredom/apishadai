<?php

namespace App\Utils\Services;

interface CryptoService
{
    public function encrypt(string $data, string $ip): string;

    public function decrypt(string $data, string $ip): string|null;
}
