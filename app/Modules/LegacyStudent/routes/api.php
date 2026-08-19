<?php

use Illuminate\Support\Facades\Route;
use App\Modules\LegacyStudent\Http\Controllers\PublicLegacyStudentController;
use App\Modules\LegacyStudent\Http\Controllers\AdminLegacyStudentController;

/*
|--------------------------------------------------------------------------
| Legacy Student API Routes
|--------------------------------------------------------------------------
|
| Publiques  -> consommées par le site vitrine (app-cap)
| Admin      -> consommées par le progiciel (app-cap-frontend), protégées
|               par auth:sanctum (ou ton middleware d'auth habituel) + rôle
|
*/

Route::prefix('api/legacy-students')->group(function () {

    // --- Endpoints publics (site vitrine) ---
    Route::get('/check/{matricule}', [PublicLegacyStudentController::class, 'check']);
    Route::get('/available-filieres', [PublicLegacyStudentController::class, 'availableFilieres']);
    Route::post('/register', [PublicLegacyStudentController::class, 'register']);
});

Route::prefix('api/admin/legacy-students')
    ->middleware(['auth:sanctum']) // ajoute ton middleware de rôle ici si dispo, ex: 'role:secretaire,admin'
    ->group(function () {

        Route::get('/', [AdminLegacyStudentController::class, 'index']);
        Route::get('/export', [AdminLegacyStudentController::class, 'export']); // avant {id} pour éviter conflit de route
        Route::post('/import', [AdminLegacyStudentController::class, 'import']);
        Route::post('/bulk-status', [AdminLegacyStudentController::class, 'bulkStatus']);

        Route::get('/{id}', [AdminLegacyStudentController::class, 'show']);
        Route::post('/', [AdminLegacyStudentController::class, 'store']);
        Route::put('/{id}', [AdminLegacyStudentController::class, 'update']);
        Route::patch('/{id}/status', [AdminLegacyStudentController::class, 'updateStatus']);
    });