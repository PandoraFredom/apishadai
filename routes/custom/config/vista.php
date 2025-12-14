   <?php
    use App\Http\Controllers\API\VistasController;

    use Illuminate\Support\Facades\Route;

    Route::prefix('vista')->group(function () {
        Route::get('/all', [VistasController::class, 'index']);
        Route::post('/create', [VistasController::class, 'store']);
        Route::get('/find/{id}', [VistasController::class, 'show']);
        Route::put('/update', [VistasController::class, 'update']);
        Route::delete('/delete/{id}', [VistasController::class, 'destroy']);
        Route::get('/findbymodule/{id}', [VistasController::class, 'findbyModule']);
        Route::get('/estados', [VistasController::class, 'estadosList']);
        Route::get('/modulos', [VistasController::class, 'modulosList']);

        //cargar acciones de la vista
        Route::get('/acciones/{id}', [VistasController::class, 'acctionList']);
        Route::post('/createaction', [VistasController::class, 'createAccion']);
        Route::delete('/deleteaction/{id}', [VistasController::class, 'deleteAccion']);
    });
