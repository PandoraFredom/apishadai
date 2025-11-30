<?php


use Illuminate\Support\Facades\Route;

  Route::prefix('vistaestado')->group(function () {
        Route::get('/all', [App\Http\Controllers\API\VistaEstadoController::class, 'index']);
        Route::post('/create', [App\Http\Controllers\API\VistaEstadoController::class, 'store']);
        Route::get('/find/{id}', [App\Http\Controllers\API\VistaEstadoController::class, 'show']);
        //   Route::put('/update/{id}', [App\Http\Controllers\API\VistaEstadoController::class, 'update']);
        //   Route::delete('/delete/{id}', [App\Http\Controllers\API\VistaEstadoController::class, 'destroy']);
    });