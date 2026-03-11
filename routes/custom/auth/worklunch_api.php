<?php

use App\Http\Controllers\WorkLunchController;
use Illuminate\Support\Facades\Route;

Route::prefix('worklunch')->group(function () {
    //show
    Route::get('/find/{id}', [WorkLunchController::class, 'show']);
    Route::post('/work', [WorkLunchController::class, 'work']);
    Route::post('/lunch', [WorkLunchController::class, 'lunch']);
});
