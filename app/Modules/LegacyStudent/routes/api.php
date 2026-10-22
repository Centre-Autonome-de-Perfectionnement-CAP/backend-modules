<?php

use App\Modules\LegacyStudent\Http\Controllers\LegacyStudentPublicController;
use App\Modules\LegacyStudent\Http\Controllers\LegacyStudentAdminController;
use App\Modules\LegacyStudent\Http\Controllers\StudentServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LegacyStudent Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api')->group(function () {

    // ── Routes publiques (déclaration étudiant et filières) ─────────────────
    // FUSION : limitation de débit (mêmes limiteurs que les routes publiques
    // du site vitrine, définis dans AttestationServiceProvider).
    Route::middleware('throttle:attestation-public-read')->group(function () {
        Route::get('/legacy-students/available-filieres', [LegacyStudentPublicController::class, 'availableFilieres']);
        Route::get('/v1/legacy-students/available-filieres', [LegacyStudentPublicController::class, 'availableFilieres']);
    });
    Route::middleware('throttle:attestation-public-write')->group(function () {
        Route::post('/legacy-students/register', [LegacyStudentPublicController::class, 'register']);
        Route::post('/v1/legacy-students/register', [LegacyStudentPublicController::class, 'register']);
        Route::post('/legacy-students/lookup-by-name', [LegacyStudentPublicController::class, 'lookupByName']);
        Route::post('/v1/legacy-students/lookup-by-name', [LegacyStudentPublicController::class, 'lookupByName']);
    });

    // ── FUSION develop/main (décision validée) ─────────────────────────────
    // Les adresses publiques /api/attestations/* (statut, bulletin-status,
    // demandes, bulletins, suivi, complément) appartiennent au module
    // Attestation/Demandes (circuit de validation + notifications). Les
    // versions provisoires de StudentServiceController sur ces mêmes adresses
    // ne sont plus enregistrées : chargées après, elles remplaçaient
    // silencieusement le circuit officiel. Les anciennes références REF-xxxxxx
    // restent consultables : le module Attestation délègue à ce contrôleur.

    // Rétrocompatibilité - ancien endpoint bulletins (conservé)
    Route::get('/bulletins/status', [StudentServiceController::class, 'bulletinStatus']);

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
