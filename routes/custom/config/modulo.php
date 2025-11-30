<?php


use Illuminate\Support\Facades\Route;


Route::prefix('modulo')->group(function () {
    Route::get('/all', [App\Http\Controllers\API\ModulosController::class, 'index']);
    Route::post('/create', [App\Http\Controllers\API\ModulosController::class, 'store']);
    Route::get('/find/{id}', [App\Http\Controllers\API\ModulosController::class, 'show']);
    Route::put('/update', [App\Http\Controllers\API\ModulosController::class, 'update']);
    Route::delete('/delete/{id}', [App\Http\Controllers\API\ModulosController::class, 'destroy']);
    Route::get('/estados', [App\Http\Controllers\API\ModuloEstadoController::class, 'index']);
});
