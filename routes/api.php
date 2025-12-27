<?php


use Illuminate\Support\Facades\Route;


Route::post('auth/session/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);



Route::group(['middleware' => ['auth:api']], function () {


    //==================================AUTH===============================
    require __DIR__ . '/custom/AuthModule_api.php';

    //==================================CONFIG=============================

    require __DIR__ . '/custom/ConfigModule_api.php';
});
