<?php

namespace App\Interfaces\WorkLunch;

use App\Interfaces\RepositoryInterface;
use App\Models\WorkLunch;
use Illuminate\Database\Eloquent\Collection;


interface WorkLunchService extends RepositoryInterface
{
    public function workService(int $userId, string $dateTime, int $deviceId): WorkLunch;
    public function lunchService(int $userId, string $dateTime): WorkLunch;
    public function findByUserBetweenDates(int $userId, string $startDate, string $endDate): Collection;
}
