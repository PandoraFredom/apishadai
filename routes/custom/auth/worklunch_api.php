<?php

use App\Http\Controllers\WorkLunchController;
use Illuminate\Support\Facades\Route;

Route::prefix('worklunch')->group(function () {
    Route::get('/today', [WorkLunchController::class, 'today']);
    Route::post('/work', [WorkLunchController::class, 'work']);
    Route::post('/lunch', [WorkLunchController::class, 'lunch']);
});
