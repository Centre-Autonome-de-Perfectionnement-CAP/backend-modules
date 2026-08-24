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
    Route::get('/legacy-students/available-filieres', [LegacyStudentPublicController::class, 'availableFilieres']);
    Route::get('/v1/legacy-students/available-filieres', [LegacyStudentPublicController::class, 'availableFilieres']);
    Route::post('/legacy-students/register', [LegacyStudentPublicController::class, 'register']);
    Route::post('/v1/legacy-students/register', [LegacyStudentPublicController::class, 'register']);
    Route::post('/legacy-students/lookup-by-name', [LegacyStudentPublicController::class, 'lookupByName']);
    Route::post('/v1/legacy-students/lookup-by-name', [LegacyStudentPublicController::class, 'lookupByName']);

    // ── Endpoints unifiés de services étudiants ────────────────────────────
    // Recherche par matricule dans les DEUX tables (récents + anciens)

    // Statut attestations (remplace l'ancien endpoint legacy-only)
    Route::get('/attestations/status', [StudentServiceController::class, 'attestationsStatus']);

    // Statut bulletins — NOUVEAU endpoint attendu par DemandesBulletinForm
    Route::get('/attestations/bulletin-status', [StudentServiceController::class, 'bulletinStatus']);

    // Soumission demande d'attestation — NOUVEAU
    Route::post('/attestations/demandes', [StudentServiceController::class, 'submitAttestation']);

    // Soumission demande de bulletin — NOUVEAU
    Route::post('/attestations/bulletins', [StudentServiceController::class, 'submitBulletin']);

    // Suivi d'une demande par référence — NOUVEAU
    Route::get('/attestations/demandes/suivi', [StudentServiceController::class, 'suiviDemande']);

    // Rechercher une demande de complément — NOUVEAU
    Route::get('/attestations/demandes/complement/find', [StudentServiceController::class, 'findComplement']);

    // Soumettre un complément de dossier — NOUVEAU
    Route::post('/attestations/demandes/complement', [StudentServiceController::class, 'submitComplement']);

    // Rétrocompatibilité — ancien endpoint bulletins (conservé)
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
