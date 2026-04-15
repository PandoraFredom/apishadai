<?php

use Illuminate\Support\Facades\Route;

Route::prefix('apputils')->group(function () {
    Route::get('/check', [App\Http\Controllers\AppConfigController::class, 'checkVersion']);

});
