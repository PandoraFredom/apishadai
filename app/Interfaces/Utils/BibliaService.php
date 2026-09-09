<?php

namespace App\Interfaces\Utils;

use App\DTO\Utils\BibliaDTO;

interface BibliaService
{
    public function getRandom(): BibliaDTO;
}
