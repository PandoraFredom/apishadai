<?php


use Illuminate\Support\Facades\Route;

Route::prefix('departamentos')->group(function () {
    Route::get('/all', [App\Http\Controllers\API\DepartamentoController::class, 'index']);
    Route::get('/find/{id}', [App\Http\Controllers\API\DepartamentoController::class, 'show']);
    Route::post('/create', [App\Http\Controllers\API\DepartamentoController::class, 'store']);
    Route::put('/update/{id}', [App\Http\Controllers\API\DepartamentoController::class, 'update']);
    Route::delete('/delete/{id}', [App\Http\Controllers\API\DepartamentoController::class, 'destroy']);
});