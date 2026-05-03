<?php

namespace App\Utils\Repositories;

use App\Utils\services\SingleHashService;

class SingleHashRepository implements SingleHashService
{
    public function genHash(string $var): string
    {
        $key = config('app.key') ?? env('APP_KEY');
        return hash_hmac('sha256', $var, $key);
    }
}
