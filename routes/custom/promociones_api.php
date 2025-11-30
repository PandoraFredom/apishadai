<?php

use Illuminate\Support\Facades\Route;

Route::prefix('promocion')->group(function () {
    Route::get('/all', [App\Http\Controllers\API\PromocionesController::class, 'index']);
    Route::get('/find/{id}', [App\Http\Controllers\API\PromocionesController::class, 'show']);
    Route::post('/create', [App\Http\Controllers\API\PromocionesController::class, 'store']);
    Route::put('/update/{id}', [App\Http\Controllers\API\PromocionesController::class, 'update']);
    Route::delete('/delete/{id}', [App\Http\Controllers\API\PromocionesController::class, 'destroy']);
    Route::get('/active', [App\Http\Controllers\API\PromocionesController::class, 'getactive']);
    Route::get('/filter', [App\Http\Controllers\API\PromocionesController::class, 'filter']);
});
