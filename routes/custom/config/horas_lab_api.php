<?php

use App\Http\Controllers\HorasLabController;
use Illuminate\Support\Facades\Route;

Route::prefix('horas-lab')->group(function () {
    // Route::get('/listar', [HorasLabController::class, 'index']);
    // Route::get('/buscar/{id}', [HorasLabController::class, 'show'])
    //     ->whereNumber('id');
    Route::get('/find/{userId}', [HorasLabController::class, 'showByUser'])->whereNumber('userId');
    Route::post('/save', [HorasLabController::class, 'store']);
    Route::delete('/eliminar/{id}', [HorasLabController::class, 'destroy'])->whereNumber('id');
});
