<?php

use App\Modules\Alumni\Http\Controllers\AlumniController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Alumni Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api')->group(function () {
    // Route publique — soumettre une fiche alumni
    Route::post('/alumni', [AlumniController::class, 'store'])->name('alumni.store');

    // Routes admin (protégées)
    Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
        Route::get('/alumni', [AlumniController::class, 'index'])->name('alumni.index');
    });
});
