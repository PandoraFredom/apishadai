<?php

use Illuminate\Support\Facades\Route;


    Route::prefix('promociones')->group(function () {
        Route::get('/listar', [App\Http\Controllers\API\PromocionesController::class, 'index']);
        Route::get('/buscar/{id}', [App\Http\Controllers\API\PromocionesController::class, 'show']);
        Route::post('/crear', [App\Http\Controllers\API\PromocionesController::class, 'store']);
        Route::put('/actualizar', [App\Http\Controllers\API\PromocionesController::class, 'update']);
        Route::delete('/eliminar/{id}', [App\Http\Controllers\API\PromocionesController::class, 'destroy']);

        Route::get('/estados', [App\Http\Controllers\API\PromocionesController::class, 'get_estadosList']);
        Route::get('/filter', [App\Http\Controllers\API\PromocionesController::class, 'filter']);

});
