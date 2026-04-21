<?php

use Illuminate\Support\Facades\Route;

Route::prefix('sorteos')->group(
    function () {

        Route::get('/filter', [App\Http\Controllers\Reportes\SorteosReporteController::class, 'filter']);
    }

);
