<?php

use App\Http\Controllers\API\Farma\LaboratoriosController;
use Illuminate\Support\Facades\Route;

Route::prefix('laboratorios')->group(function (): void {
    Route::get('/listar', [LaboratoriosController::class, 'index']);
    Route::post('/crear', [LaboratoriosController::class, 'store']);
    Route::get('/buscar/{id}', [LaboratoriosController::class, 'show']);
    Route::put('/actualizar', [LaboratoriosController::class, 'update']);
    Route::delete('/eliminar/{id}', [LaboratoriosController::class, 'destroy']);
    Route::get('/imagen/{id}', [LaboratoriosController::class, 'getImage']);
});
