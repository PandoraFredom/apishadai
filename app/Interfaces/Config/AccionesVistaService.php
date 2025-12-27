<?php

namespace App\Interfaces\Config;

use App\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface AccionesVistaService extends RepositoryInterface
{
    public function findByVista(int $vistaId): Collection;
}
