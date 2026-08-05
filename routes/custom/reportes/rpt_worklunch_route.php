<?php

use App\Http\Controllers\Reportes\RptWorkLunchController;
use Illuminate\Support\Facades\Route;

Route::prefix('worklunch')->group(function () {
    Route::get('/filter', [RptWorkLunchController::class, 'filter']);
});
