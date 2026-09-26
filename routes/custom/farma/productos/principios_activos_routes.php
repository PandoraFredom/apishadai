<?php

use App\Http\Controllers\API\Farma\Productos\PrincipalActivoController;
use Illuminate\Support\Facades\Route;

Route::prefix('/principios-activos')->group(function (): void {
    Route::get('/listar', [PrincipalActivoController::class, 'index']);
    Route::get('/concentraciones', [PrincipalActivoController::class, 'concentraciones']);
    Route::get('/filter_principios_activos', [PrincipalActivoController::class, 'filterPrincipiosActivos']);
    Route::get('/filter_concentraciones', [PrincipalActivoController::class, 'filterConcentraciones']);
    Route::post('/crear', [PrincipalActivoController::class, 'store']);
    Route::get('/buscar/{id}', [PrincipalActivoController::class, 'show'])->whereNumber('id');
    Route::put('/update/{id}', [PrincipalActivoController::class, 'update'])->whereNumber('id');
    Route::delete('/eliminar/{id}', [PrincipalActivoController::class, 'destroy'])->whereNumber('id');
});
