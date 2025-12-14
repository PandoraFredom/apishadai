<?php


use Illuminate\Support\Facades\Route;


Route::post('auth/session/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
require __DIR__ . '/custom/ConfigModule_api.php';
Route::group(['middleware' => ['auth:api']], function () {


    //==================================AUTH===============================
    require __DIR__ . '/custom/AuthModule_api.php';

    //==================================CONFIG=============================

});
