<?php


use Illuminate\Support\Facades\Route;

Route::prefix('departamentos')->group(function () {
    Route::get('/all', [\App\Http\Controllers\Ubicacion\DepartamentoController::class, 'index']);
    Route::post('/create', [\App\Http\Controllers\Ubicacion\DepartamentoController::class, 'store']);
    Route::get('/find', [\App\Http\Controllers\Ubicacion\DepartamentoController::class, 'show']);
    Route::put('/update/{id}', [\App\Http\Controllers\Ubicacion\DepartamentoController::class, 'update']);
    Route::delete('/delete/{id}', [\App\Http\Controllers\Ubicacion\DepartamentoController::class, 'destroy']);
});
