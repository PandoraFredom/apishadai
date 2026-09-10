<?php

use App\Http\Controllers\API\Farma\Productos\FamiliaController;
use Illuminate\Support\Facades\Route;

Route::prefix('/familias')->group(function (): void {
    Route::get('/listar', [FamiliaController::class, 'index']);
    Route::post('/crear', [FamiliaController::class, 'store']);
    Route::get('/buscar/{id}', [FamiliaController::class, 'show']);
    Route::put('/update/{id}', [FamiliaController::class, 'update'])->whereNumber('id');
    Route::delete('/eliminar/{id}', [FamiliaController::class, 'destroy']);
});
