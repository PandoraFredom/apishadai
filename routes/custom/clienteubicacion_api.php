<?php

use App\Http\Controllers\API\ClienteUbicacionController;
use Illuminate\Support\Facades\Route;


Route::prefix('clienteubicacion')->group(function () {
    Route::get('/all', [ClienteUbicacionController::class, 'index']);
    Route::get('/find/{id}', [ClienteUbicacionController::class, 'show']);
    Route::post('/create', [ClienteUbicacionController::class, 'store']);
    Route::put('/update/{id}', [ClienteUbicacionController::class, 'update']);
    Route::delete('/delete/{id}', [ClienteUbicacionController::class, 'destroy']);
});
