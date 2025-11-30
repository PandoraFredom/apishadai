<?php

use Illuminate\Support\Facades\Route;

Route::prefix('tiposTiempo')->group(function () {
    Route::get('/all', 'App\Http\Controllers\API\TipoTiempoController@index')->name('tiposTiempo.index');
    Route::post('/create', 'App\Http\Controllers\API\TipoTiempoController@store')->name('tiposTiempo.store');
    Route::get('/find/{id}', 'App\Http\Controllers\API\TipoTiempoController@show')->name('tiposTiempo.show');
    Route::put('/update/{id}', 'App\Http\Controllers\API\TipoTiempoController@update')->name('tiposTiempo.update');
    Route::delete('/delete{id}', 'App\Http\Controllers\API\TipoTiempoController@destroy')->name('tiposTiempo.destroy');
});