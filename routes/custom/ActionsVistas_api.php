<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ActionsVistasController;

Route::prefix('actionsvistas')->group(function () {

    Route::get('/all', [ActionsVistasController::class, 'index']);
    Route::post('/create', [ActionsVistasController::class, 'store']);
    Route::get('/find/{id}', [ActionsVistasController::class, 'show']);
    Route::put('/update/{id}', [ActionsVistasController::class, 'update']);
    Route::delete('/delete/{id}', [ActionsVistasController::class, 'destroy']);
    Route::get('/findbyvista/{id}', [ActionsVistasController::class, 'findByVista']);
    
});