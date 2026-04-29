<?php

namespace App\Interfaces\Config;

use App\Http\Requests\Util\FilterRequest;
use App\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface StockRepositoryInterface extends RepositoryInterface
{
	public function get_estadosList(): Collection;
}
