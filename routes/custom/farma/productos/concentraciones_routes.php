<?php

use App\Http\Controllers\API\Farma\Productos\ConcentracionController;
use Illuminate\Support\Facades\Route;

Route::prefix('productos/catalogos/concentraciones')->group(function (): void {
    Route::get('/listar', [ConcentracionController::class, 'index']);
    Route::post('/crear', [ConcentracionController::class, 'store']);
    Route::get('/buscar/{id}', [ConcentracionController::class, 'show'])->whereNumber('id');
    Route::put('/update/{id}', [ConcentracionController::class, 'update'])->whereNumber('id');
    Route::delete('/eliminar/{id}', [ConcentracionController::class, 'destroy'])->whereNumber('id');
});
