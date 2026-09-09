<?php

use App\Http\Controllers\Reportes\RptWorkLunchController;
use Illuminate\Support\Facades\Route;

Route::prefix('worklunch')->group(
    function () {
        Route::get('/filter', [RptWorkLunchController::class, 'filter']);
        Route::get('/listUsuarios', [RptWorkLunchController::class, 'getUsuariosList']);
        Route::get('/horasLab/{userId}', [RptWorkLunchController::class, 'getHorasLabByUser'])
            ->whereNumber('userId');
    }
);
