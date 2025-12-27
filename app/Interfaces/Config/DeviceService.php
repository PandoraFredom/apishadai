<?php

namespace App\Interfaces\Config;



use App\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface DeviceService  extends RepositoryInterface
{
    public function get_stocksList(): Collection;
    public function get_estadosList(): Collection;
}
