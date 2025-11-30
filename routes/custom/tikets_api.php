<?php 


use Illuminate\Support\Facades\Route;

Route::prefix('tikets')->group(function () {
    Route::get('/all', [App\Http\Controllers\API\tiketsController::class, 'index']);
    Route::post('/create/{uuid}', [App\Http\Controllers\API\tiketsController::class, 'store']);
    Route::get('/find/{id}', [App\Http\Controllers\API\tiketsController::class, 'show']);
    Route::put('/update{id}', [App\Http\Controllers\API\tiketsController::class, 'update']);
    Route::delete('/delete/{id}', [App\Http\Controllers\API\tiketsController::class, 'destroy']);
    Route::get('/filter', [App\Http\Controllers\API\tiketsController::class, 'filter']);
});