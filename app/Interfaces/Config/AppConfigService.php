<?php

namespace App\Interfaces\Config;

use App\Interfaces\RepositoryInterface;

interface AppConfigService extends RepositoryInterface
{
    public function existVersion(string $version): bool;
}
