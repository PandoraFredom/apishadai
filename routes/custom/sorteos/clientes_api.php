<?php

use App\Http\Controllers\API\ClientesController;
use Illuminate\Support\Facades\Route;

Route::prefix('clientes')->group(function () {
    Route::get('/all', [ClientesController::class, 'index']);
    Route::get('/find/{id}', [ClientesController::class, 'show']);
    Route::post('/create', [ClientesController::class, 'store']);
    Route::put('/update', [ClientesController::class, 'update']);
    Route::delete('/delete/{id}', [ClientesController::class, 'destroy']);


    Route::get('/filter', [ClientesController::class, 'filter']);
});
