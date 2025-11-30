   <?php


    use Illuminate\Support\Facades\Route;

    Route::prefix('vista')->group(function () {
        Route::get('/all', [App\Http\Controllers\API\VistasController::class, 'index']);
        Route::post('/create', [App\Http\Controllers\API\VistasController::class, 'store']);
        Route::get('/find/{id}', [App\Http\Controllers\API\VistasController::class, 'show']);
        Route::put('/update', [App\Http\Controllers\API\VistasController::class, 'update']);
        Route::delete('/delete/{id}', [App\Http\Controllers\API\VistasController::class, 'destroy']);
        Route::get('/findbymodule/{id}', [App\Http\Controllers\API\VistasController::class, 'findbyModule']);

        Route::get('/estados', [App\Http\Controllers\API\VistaEstadoController::class, 'index']);
        Route::get('/modulos', [App\Http\Controllers\API\ModulosController::class, 'index']);

        //cargar acciones de la vista
        Route::get('/acciones/{id}', [App\Http\Controllers\API\ActionsVistasController::class, 'findByVista']);
        Route::post('/createaction', [App\Http\Controllers\API\ActionsVistasController::class, 'store']);
        Route::delete('deleteaction/{id}', [App\Http\Controllers\API\ActionsVistasController::class, 'destroy']);
    });
