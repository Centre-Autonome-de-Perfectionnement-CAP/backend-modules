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
use App\Modules\RH\Http\Controllers\WhatsAppGroupController;

Route::prefix('rh')->group(function () {

    Route::get('professors',             [ProfessorController::class, 'index']);
    // Liste complète non paginée, pour les selects (ex: création de contrat RH)
    Route::get('professors-select',      [ProfessorController::class, 'forSelect']);
    Route::get('grades',                 [GradeController::class, 'index']);
    Route::get('files/{file}',           [FileController::class, 'viewDocument']);
    Route::get('documents',              [DocumentManagementController::class, 'index']);
    Route::get('important-informations', [ImportantInformationController::class, 'index']);
    Route::get('academic-years',         [AcademicYearController::class, 'index']);
    Route::get('cycles',                 [CycleController::class, 'index']);

    // ─── Programmes d'un professeur ───────────────────────────────────────────
    // Route dans le groupe auth:sanctum — voir bloc ci-dessous

    // ─── Accès par token (liens email — PUBLIC, sans authentification) ─────────
    // IMPORTANT : ces routes doivent être déclarées AVANT contrats/{id}
    // sinon Laravel capture "by-token" comme valeur de {id}
    Route::get('contrats/by-token/{token}',           [ContratController::class, 'showByToken']);
    Route::post('contrats/by-token/{token}/validate', [ContratController::class, 'validateByToken']);
    Route::post('contrats/by-token/{token}/reject',   [ContratController::class, 'rejectByToken']);
    Route::get('contrats/by-token/{token}/download',  [ContratController::class, 'downloadByToken']);

    // ─── Contrats (CRUD complet — admin) ──────────────────────────────────────
    //
    // CORRECTIF SÉCURITÉ (16/08/2026) : ces routes (CRUD contrats, upload PDF,
    // autorisation, email de transfert, gestion admin-users avec attribution
    // de rôles, signataires, documents, informations importantes) étaient
    // ENTIÈREMENT accessibles SANS authentification — le commentaire
    // "Routes protégées" au-dessus d'apiResource('documents', ...) était
    // trompeur, aucun middleware n'était réellement appliqué.
    //
    // auth:sanctum ajouté ici : garantit maintenant qu'un utilisateur doit
    // au minimum être connecté. ATTENTION : aucun contrôle de RÔLE fin
    // n'existe encore à l'intérieur de ContratController / AdminUserController
    // (vérifié : aucun assertAdmin()/Gate:: nulle part dans ces deux fichiers).
    // Concrètement, N'IMPORTE QUEL compte connecté (y compris un professeur)
    // peut aujourd'hui créer/supprimer des contrats et attribuer le rôle
    // admin à n'importe qui. À corriger en ajoutant un assertAdmin() dans
    // ces contrôleurs (même pattern que WhatsAppAdminController) — décision
    // du rôle exact à autoriser laissée à l'équipe, pas prise seul ici.
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('professors/{professorId}/programs', [ContratController::class, 'professorPrograms']);

        Route::get('contrats',         [ContratController::class, 'index']);
        Route::post('contrats',        [ContratController::class, 'store']);
        Route::get('contrats/{id}',    [ContratController::class, 'show']);
        Route::put('contrats/{id}',    [ContratController::class, 'update']);
        Route::delete('contrats/{id}', [ContratController::class, 'destroy']);

        // ─── Autorisation d'un contrat validé (admin uniquement) ─────────────
        Route::post('contrats/{id}/authorize',   [ContratController::class, 'authorizeContrat']);

        // ─── Upload PDF final (admin remplace ou ajoute le PDF définitif) ────
        Route::post('contrats/{id}/upload-pdf',  [ContratController::class, 'uploadPdf']);

        // ─── Email de transfert (+ WhatsApp automatique, voir ContratController) ──
        Route::post('contrats/{id}/send-transfer-email', [ContratController::class, 'sendTransferEmail']);

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

        Route::get(
            'contrats/{contratId}/programs/{programId}/supports',
            [ContratController::class, 'listProgramSupports']
        );
        Route::post(
            'contrats/{contratId}/programs/{programId}/supports',
            [ContratController::class, 'addProgramSupport']
        );
        Route::delete(
            'contrats/{contratId}/programs/{programId}/supports/{index}',
            [ContratController::class, 'deleteProgramSupport']
        );
        // ─── Stream PDF contrat (inline ou téléchargement) ────────────────────
        Route::get('contrats/{id}/pdf',              [ContratController::class, 'streamPdf']);
        // ─── Stream signature ─────────────────────────────────────────────────
        Route::get('contrats/{id}/signature',        [ContratController::class, 'streamSignature']);
        // ─── Stream support de cours ──────────────────────────────────────────
        Route::get(
            'contrats/{contratId}/programs/{programId}/supports/{index}/stream',
            [ContratController::class, 'streamSupport']
        );
        // ─── Stream facture normalisée ────────────────────────────────────────
        Route::get(
            'contrats/{contratId}/factures/{index}/stream',
            [ContratController::class, 'streamFacture']
        );
        Route::put(
            'contrats/{contratId}/programs/{programId}/monographie',
            [ContratController::class, 'updateProgramMonographie']
        );
        Route::get('professors/{professorId}/programs/{programId}', [ContratController::class, 'getProfessorProgram']);
        Route::post('/contrats/{id}/factures-normalisees', [ContratController::class, 'uploadFacturesNormalisees']);

        // ─── Groupes WhatsApp (liens WhatsApp des filières) ───────────────────
        Route::get('whatsapp-groups',              [WhatsAppGroupController::class, 'index']);
        Route::put('whatsapp-groups/{department}', [WhatsAppGroupController::class, 'update']);
        Route::delete('whatsapp-groups/{department}', [WhatsAppGroupController::class, 'destroy']);
    });

    // ─── Contrats du professeur connecté ─────────────────────────────────────
    // Protégé par Sanctum — accepte tout utilisateur authentifié (User ou Professor)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('professor/my-contrats', [ContratController::class, 'myContrats']);
        Route::get('professor/my-factures', [ContratController::class, 'myFactures']);
    });
});