<?php

use Illuminate\Support\Facades\Route;

Route::prefix('device')->group(function () {
    Route::get('/listar', [App\Http\Controllers\DeviceController::class, 'index']);
    Route::get('/buscar/{id}', [App\Http\Controllers\DeviceController::class, 'show']);
    Route::post('/crear', [App\Http\Controllers\DeviceController::class, 'store']);
    Route::put('/actualizar', [App\Http\Controllers\DeviceController::class, 'update']);
    Route::delete('/eliminar/{id}', [App\Http\Controllers\DeviceController::class, 'destroy']);

    //estados
    Route::get('/estados', [App\Http\Controllers\API\DeviceEstadoController::class, 'index']);
    //stocks
    Route::get('/stocks', [App\Http\Controllers\API\StocksController::class, 'index']);

});



