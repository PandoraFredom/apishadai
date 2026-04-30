<?php


use Illuminate\Support\Facades\Route;


Route::post('auth/session/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);

 require __DIR__ . '/custom/ConfigModule_api.php';


Route::group(['middleware' => ['auth:api']], function () {

    require __DIR__ . '/custom/AuthModule_api.php';
    //==================================AUTH==================================

    //==================================CONFIG=================================


    //==================================PROMOCIONES=============================
    require __DIR__ . '/custom/sorteos_api.php';
    //==================================REPORTES=============================

    require __DIR__ . '/custom/ReportesModule_api.php';
});
