<?php

use Illuminate\Support\Facades\Route;

/*MODULE AUTH*/

Route::prefix('auth')->group(function () {

    Route::prefix('/session')->group(function () {
        Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout']);

        Route::post('/register', [App\Http\Controllers\Auth\AuthController::class, 'register']);
        Route::put('/update', [App\Http\Controllers\Auth\AuthController::class, 'update']);
    });
});
