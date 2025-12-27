<?php

namespace App\Utils;

use App\Models\TipoTiempo;
use Carbon\Carbon;

class LifetimeResolver
{
    public static function resolve(TipoTiempo $tipoTiempo): int
    {
        $cantidad = (int) $tipoTiempo->cantidad;

        $fecha = match ($tipoTiempo->unidad) {
            'minutes' => now()->addMinutes($cantidad),
            'hours'   => now()->addHours($cantidad),
            'days'    => now()->addDays($cantidad),
            'weeks'   => now()->addWeeks($cantidad),
            'months'  => now()->addMonths($cantidad),
            'years'   => now()->addYears($cantidad),
            default   => Carbon::create(2099, 12, 31, 23, 59, 59),
        };

        return $fecha->timestamp;
    }
}
