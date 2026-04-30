<?php

use Illuminate\Support\Facades\Route;
use App\Modules\RH\Http\Controllers\ProfessorController;
use App\Modules\RH\Http\Controllers\AdminUserController;
use App\Modules\RH\Http\Controllers\GradeController;
use App\Modules\RH\Http\Controllers\SignataireController;
use App\Modules\RH\Http\Controllers\DocumentManagementController;
use App\Modules\RH\Http\Controllers\ImportantInformationController;
use App\Modules\RH\Http\Controllers\FileController;
use App\Modules\RH\Http\Controllers\ContratController;
use App\Modules\RH\Http\Controllers\AcademicYearController;
use App\Modules\RH\Http\Controllers\CycleController;

Route::prefix('rh')->group(function () {

    Route::get('professors',             [ProfessorController::class, 'index']);
    Route::get('grades',                 [GradeController::class, 'index']);
    Route::get('files/{file}',           [FileController::class, 'viewDocument']);
    Route::get('documents',              [DocumentManagementController::class, 'index']);
    Route::get('important-informations', [ImportantInformationController::class, 'index']);
    Route::get('academic-years',         [AcademicYearController::class, 'index']);
    Route::get('cycles',                 [CycleController::class, 'index']);

    // ─── Programmes d'un professeur ───────────────────────────────────────────
    Route::get('professors/{professorId}/programs', [ContratController::class, 'professorPrograms']);

    // ─── Accès par token (liens email — PUBLIC, sans authentification) ─────────
    // IMPORTANT : ces routes doivent être déclarées AVANT contrats/{id}
    // sinon Laravel capture "by-token" comme valeur de {id}
    Route::get('contrats/by-token/{token}',           [ContratController::class, 'showByToken']);
    Route::post('contrats/by-token/{token}/validate', [ContratController::class, 'validateByToken']);
    Route::post('contrats/by-token/{token}/reject',   [ContratController::class, 'rejectByToken']);
    Route::get('contrats/by-token/{token}/download',  [ContratController::class, 'downloadByToken']);

    // ─── Contrats (CRUD complet — admin) ──────────────────────────────────────
    Route::get('contrats',         [ContratController::class, 'index']);
    Route::post('contrats',        [ContratController::class, 'store']);
    Route::get('contrats/{id}',    [ContratController::class, 'show']);
    Route::put('contrats/{id}',    [ContratController::class, 'update']);
    Route::delete('contrats/{id}', [ContratController::class, 'destroy']);

    // ─── Autorisation d'un contrat validé (admin uniquement) ─────────────────
    Route::post('contrats/{id}/authorize',   [ContratController::class, 'authorizeContrat']);

    // ─── Upload PDF final (admin remplace ou ajoute le PDF définitif) ─────────
    Route::post('contrats/{id}/upload-pdf',  [ContratController::class, 'uploadPdf']);

    // ─── Email de transfert ───────────────────────────────────────────────────
    Route::post('contrats/{id}/send-transfer-email', [ContratController::class, 'sendTransferEmail']);

    // ─── Routes protégées ─────────────────────────────────────────────────────
    Route::apiResource('documents', DocumentManagementController::class)->except(['index']);

    Route::get('important-informations/admin', [ImportantInformationController::class, 'indexAdmin']);
    Route::apiResource('important-informations', ImportantInformationController::class)->except(['index']);

    Route::apiResource('professors', ProfessorController::class)->only(['store', 'show', 'update', 'destroy']);

    Route::apiResource('admin-users', AdminUserController::class);
    Route::post('admin-users/{adminUser}/roles/attach', [AdminUserController::class, 'attachRole']);
    Route::post('admin-users/{adminUser}/roles/detach', [AdminUserController::class, 'detachRole']);
    Route::get('admin-users-statistics', [AdminUserController::class, 'statistics']);

    Route::apiResource('signataires', SignataireController::class);
    Route::get('banks', [ProfessorController::class, 'getBanks']);

    Route::get('roles', function () {
        return response()->json([
            'success' => true,
            'data'    => \App\Modules\Stockage\Models\Role::select('id', 'name', 'slug')->get(),
        ]);
    });

    // ─── Contrats du professeur connecté ─────────────────────────────────────
    // Protégé par Sanctum — accepte tout utilisateur authentifié (User ou Professor)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('professor/my-contrats', [ContratController::class, 'myContrats']);
    });
});
