<?php


use Illuminate\Support\Facades\Route;

Route::prefix('moduloestado')->group(function () {
    Route::get('/all', [App\Http\Controllers\API\ModuloEstadoController::class, 'index']);
    Route::post('/create', [App\Http\Controllers\API\ModuloEstadoController::class, 'store']);
    Route::get('/find/{id}', [App\Http\Controllers\API\ModuloEstadoController::class, 'show']);
});
