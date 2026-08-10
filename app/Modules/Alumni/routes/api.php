<?php

use App\Modules\Alumni\Http\Controllers\AlumniController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Alumni Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api')->group(function () {

    // ── Route publique — soumettre une fiche alumni ─────────────────────────
    Route::post('/alumni', [AlumniController::class, 'store'])->name('alumni.store');

    // ── Routes admin (protégées par auth:sanctum) ───────────────────────────
    Route::middleware(['auth:sanctum'])->prefix('admin/alumni')->group(function () {

        // Dashboard KPI
        Route::get('/dashboard', [AlumniController::class, 'dashboard'])->name('alumni.dashboard');

        // Liste paginée avec filtres
        Route::get('/', [AlumniController::class, 'index'])->name('alumni.index');

        // Détail d'un alumni
        Route::get('/{id}', [AlumniController::class, 'show'])->name('alumni.show');

        // Mise à jour
        Route::put('/{id}', [AlumniController::class, 'update'])->name('alumni.update');

        // Suppression
        Route::delete('/{id}', [AlumniController::class, 'destroy'])->name('alumni.destroy');
    });
});
