<?php

use Illuminate\Support\Facades\Route;

Route::prefix('municipios')->group(function () {
    Route::get('/all', [\App\Http\Controllers\Ubicacion\MunicipiosController::class, 'index']);
    Route::post('/create', [\App\Http\Controllers\Ubicacion\MunicipiosController::class, 'store']);
    Route::get('/find', [\App\Http\Controllers\Ubicacion\MunicipiosController::class, 'show']);
    Route::put('/delete/{id}', [\App\Http\Controllers\Ubicacion\MunicipiosController::class, 'update']);

    Route::get('/bydepartamento/{did}', [\App\Http\Controllers\Ubicacion\MunicipiosController::class, 'getByDepartamento']);
});
