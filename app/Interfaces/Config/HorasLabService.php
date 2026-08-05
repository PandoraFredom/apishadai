<?php

namespace App\Interfaces\Config;

use App\Interfaces\RepositoryInterface;
use App\Models\HorasLab;

interface HorasLabService extends RepositoryInterface
{
    public function findByUser(int $userId): ?HorasLab;

    public function createSchedule(array $data): HorasLab;

    public function updateSchedule(int $id, array $data): ?HorasLab;
}
