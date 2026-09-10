<?php

use App\Http\Controllers\API\Farma\Productos\FamAdministracionController;
use Illuminate\Support\Facades\Route;

Route::prefix('/administraciones')->group(function (): void {
    Route::get('/listar', [FamAdministracionController::class, 'index']);
    Route::post('/crear', [FamAdministracionController::class, 'store']);
    Route::get('/buscar/{id}', [FamAdministracionController::class, 'show'])->whereNumber('id');
    Route::put('/actualizar/{id}', [FamAdministracionController::class, 'update'])->whereNumber('id');
    Route::delete('/eliminar/{id}', [FamAdministracionController::class, 'destroy'])->whereNumber('id');
});
