<?php

use App\Http\Controllers\API\Utils\BibliaController;
use Illuminate\Support\Facades\Route;

Route::prefix('utils/bible')->group(function (): void {
    Route::get('/random', [BibliaController::class, 'random'])->name('utils.bible.random');
});
