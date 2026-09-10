<?php

use App\Http\Controllers\API\Farma\Productos\FamPresentacionController;
use Illuminate\Support\Facades\Route;

Route::prefix('/presentaciones')->group(function (): void {
    Route::get('/listar', [FamPresentacionController::class, 'index']);
    Route::post('/crear', [FamPresentacionController::class, 'store']);
    Route::get('/buscar/{id}', [FamPresentacionController::class, 'show'])->whereNumber('id');
    Route::put('/actualizar/{id}', [FamPresentacionController::class, 'update'])->whereNumber('id');
    Route::delete('/eliminar/{id}', [FamPresentacionController::class, 'destroy'])->whereNumber('id');
});
