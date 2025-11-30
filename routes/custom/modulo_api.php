<?php 

use App\Http\Controllers\API\ModuloEstadoController;
use App\Http\Controllers\API\ModulosController;
use Illuminate\Support\Facades\Route;



Route::prefix('modulo')->group(function () {
    Route::get('/all', [ModulosController::class, 'index']);
    Route::post('/create', [ModulosController::class, 'store']);
    Route::get('/find/{id}', [ModulosController::class, 'show']);
    Route::put('/update/{id}', [ModulosController::class, 'update']);
    Route::delete('/delete/{id}', [ModulosController::class, 'destroy']); 
});

Route::prefix('moduloestado')->group(function () {
    Route::get('/all', [ModuloEstadoController::class, 'index']);
    Route::post('/create', [ModuloEstadoController::class, 'store']);
    Route::get('/find/{id}', [ModuloEstadoController::class, 'show']);
    Route::put('/update/{id}', [ModuloEstadoController::class, 'update']);
    Route::delete('/delete/{id}', [ModuloEstadoController::class, 'destroy']);
});

