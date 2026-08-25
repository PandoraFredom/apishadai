   <?php

    use App\Http\Controllers\API\VistasController;

    use Illuminate\Support\Facades\Route;

    Route::prefix('vista')->group(
        function () {
            Route::get('/listar', [VistasController::class, 'index']);
            Route::post('/crear', [VistasController::class, 'store']);
            Route::get('/buscar/{id}', [VistasController::class, 'show']);
            Route::put('/actualizar/{id}', [VistasController::class, 'update']);
            Route::delete('/eliminar/{id}', [VistasController::class, 'destroy']);
            Route::get('/findbymodule/{id}', [VistasController::class, 'findbyModule']);

            Route::get('/estados', [VistasController::class, 'estadosList']);
            Route::get('/listarModulos', [VistasController::class, 'modulosList']);

            //cargar acciones de la vista
            Route::get('/acciones/{id}', [VistasController::class, 'acctionList']);
            Route::post('/createaction', [VistasController::class, 'createAccion']);
            Route::delete('/deleteaction/{id}', [VistasController::class, 'deleteAccion']);
        }
    );
