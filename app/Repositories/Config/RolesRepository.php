<?php

namespace App\Repositories\Config;

use App\Interfaces\Config\RolesRepositoryInterface;
use App\Models\Roles;
use App\Repositories\Repository;
class RolesRepository extends Repository implements RolesRepositoryInterface
{
    public function __construct(Roles $model)
    {
        parent::__construct($model);
    }
}
