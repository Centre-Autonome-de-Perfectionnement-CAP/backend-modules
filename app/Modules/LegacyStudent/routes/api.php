<?php

use App\Modules\LegacyStudent\Http\Controllers\LegacyStudentPublicController;
use App\Modules\LegacyStudent\Http\Controllers\LegacyStudentAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LegacyStudent Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api')->group(function () {

    // ── Routes publiques (déclaration étudiant et filières) ─────────────────
    // Supporte /api/legacy-students/* et /api/v1/legacy-students/*
    Route::get('/legacy-students/available-filieres', [LegacyStudentPublicController::class, 'availableFilieres']);
    Route::get('/v1/legacy-students/available-filieres', [LegacyStudentPublicController::class, 'availableFilieres']);
    Route::post('/legacy-students/register', [LegacyStudentPublicController::class, 'register']);
    Route::post('/v1/legacy-students/register', [LegacyStudentPublicController::class, 'register']);
    // Recherche par nom/prénom/date dans legacy_students (fallback du lookup-id)
    Route::post('/legacy-students/lookup-by-name', [LegacyStudentPublicController::class, 'lookupByName']);
    Route::post('/v1/legacy-students/lookup-by-name', [LegacyStudentPublicController::class, 'lookupByName']);
    // Statuts documents étudiants (anciens étudiants)
    Route::get('/attestations/status', [LegacyStudentPublicController::class, 'attestationsStatus']);
    Route::get('/bulletins/status', [LegacyStudentPublicController::class, 'bulletinsStatus']);

    // ── Routes admin (gestion scolarité) ───────────────────────────────────
    Route::prefix('admin/legacy-students')->group(function () {
        Route::get('/', [LegacyStudentAdminController::class, 'index']);
        Route::post('/', [LegacyStudentAdminController::class, 'store']);
        Route::put('/{id}', [LegacyStudentAdminController::class, 'update']);
        Route::post('/bulk-validate', [LegacyStudentAdminController::class, 'bulkValidate']);
        Route::post('/bulk-reject', [LegacyStudentAdminController::class, 'bulkReject']);

        // Demandes de services
        Route::get('/services', [LegacyStudentAdminController::class, 'servicesIndex']);
        Route::patch('/services/{id}/status', [LegacyStudentAdminController::class, 'updateServiceStatus']);

        // Actions individuelles
        Route::post('/{id}/validate', [LegacyStudentAdminController::class, 'validateStudent']);
        Route::post('/{id}/reject', [LegacyStudentAdminController::class, 'rejectStudent']);

        // Import Excel
        Route::post('/import', [LegacyStudentAdminController::class, 'import']);

        // Dossier académique (notes, résultats, mémoire)
        Route::get('/{id}/academic-records', [LegacyStudentAdminController::class, 'academicRecordsIndex']);
        Route::post('/{id}/academic-records', [LegacyStudentAdminController::class, 'academicRecordsStore']);
        Route::put('/{id}/academic-records/{recordId}', [LegacyStudentAdminController::class, 'academicRecordsUpdate']);
        Route::delete('/{id}/academic-records/{recordId}', [LegacyStudentAdminController::class, 'academicRecordsDestroy']);
    });
});
