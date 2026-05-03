<?php

namespace App\Utils\Services;

interface SingleHashService
{
    public function genHash(string $var): string;
}
