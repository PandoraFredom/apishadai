<?php

namespace App\Interfaces\Laboratorios;

use App\Interfaces\RepositoryInterface;

interface LaboratorioService extends RepositoryInterface
{
    public function getImage(int $id): ?string;
}
