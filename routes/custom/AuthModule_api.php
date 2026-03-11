<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    //---------------------------Session---------------------------------
    require __DIR__ . '/auth/session_api.php';
    //---------------------------WorkLunch---------------------------------
    require __DIR__ . '/auth/worklunch_api.php';
});
