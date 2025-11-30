<?php

use Illuminate\Support\Facades\Route;

Route::prefix('municipios')->group(function () {
    Route::get('/all', [App\Http\Controllers\API\MunicipiosController::class, 'index']);
    Route::get('/find/{id}', [App\Http\Controllers\API\MunicipiosController::class, 'show']);
    Route::post('/create', [App\Http\Controllers\API\MunicipiosController::class, 'store']);
    Route::put('/update/{id}', [App\Http\Controllers\API\MunicipiosController::class, 'update']);
    Route::delete('/delete/{id}', [App\Http\Controllers\API\MunicipiosController::class, 'destroy']);
    Route::get('/findbydepartamento/{id}', [App\Http\Controllers\API\MunicipiosController::class, 'findbydepartamento']);
});
