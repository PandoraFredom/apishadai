<?php

use App\Http\Controllers\API\PromoEstadoController;
use Illuminate\Support\Facades\Route;

Route::prefix('promoestado')->group(function () {

    Route::get('/all', [PromoEstadoController::class, 'index']);
    Route::get('/find/{id}', [PromoestadoController::class, 'show']);
    Route::post('/create', [PromoestadoController::class, 'store']);
    Route::put('/update/{id}', [PromoestadoController::class, 'update']);
    Route::delete('/delete/{id}', [PromoestadoController::class, 'destroy']);
});
