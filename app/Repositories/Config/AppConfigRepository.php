<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\AppConfigService;
use App\Models\AppConfig;
use App\Repositories\Repository;

class AppConfigRepository extends Repository implements AppConfigService
{
    public function __construct(AppConfig $model)
    {
        parent::__construct($model);
    }

    public function existVersion(string $version): bool
    {
        return $this->whereFirst(['appv' => $version]) !== null;
    }
}
