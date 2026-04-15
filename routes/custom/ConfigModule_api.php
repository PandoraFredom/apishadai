<?php

use Illuminate\Support\Facades\Route;

Route::prefix('config')->group(function () {
    //---------------------------VISTA---------------------------------
    require __DIR__ . '/config/vista.php';
    //---------------------------VISTA ESTADO---------------------------
    require __DIR__ . '/config/vista_estado.php';
    //---------------------------Modulo---------------------------------
    require __DIR__ . '/config/modulo.php';
    //---------------------------Modulo Estados-------------------------
    require __DIR__ . '/config/modulo_estado.php';
    //---------------------------Usuarios-------------------------------
    require __DIR__ . '/config/user_api.php';
    //---------------------------Permisos----------------------------------
    require __DIR__ . '/config/permisos_api.php';
    //---------------------------Stocks---------------------------------
    require __DIR__ . '/config/stocks_api.php';
    //---------------------------Stock Estados---------------------------
    require __DIR__ . '/config/stocks_estado_api.php';
    //---------------------------Dispositivos---------------------------------
    require __DIR__ . '/config/device_api.php';
        //---------------------------AppConfig---------------------------------
    require __DIR__ . '/config/app_config_api.php';
});
