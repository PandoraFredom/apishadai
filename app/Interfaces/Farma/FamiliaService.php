<?php

namespace App\Interfaces\Farma;

use App\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface FamiliaService extends RepositoryInterface
{
    public function administraciones(): Collection;

    public function presentaciones(): Collection;
}
