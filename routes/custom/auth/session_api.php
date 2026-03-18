<?php

use Illuminate\Support\Facades\Route;

Route::prefix('/session')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout']);
});
