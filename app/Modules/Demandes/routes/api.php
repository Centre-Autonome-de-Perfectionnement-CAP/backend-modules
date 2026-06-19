<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Demandes\Http\Controllers\DocumentRequestController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestTransitionController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestHistoryController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestStatsController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestBadgeController;  // ← AJOUT 11.1

/*
 * Module Demandes — workflow de gestion des demandes de documents
 *
 * Base URL : /api/attestations   (inchangée — aucun impact côté frontend)
 */

Route::prefix('api/attestations')->middleware('auth:sanctum')->group(function () {

    // ── Listing + détail ──────────────────────────────────────────────────────
    Route::get('document-requests',             [DocumentRequestController::class, 'index']);
    Route::get('document-requests/badge-count', DocumentRequestBadgeController::class);
    Route::get('document-requests/{id}',        [DocumentRequestController::class, 'show']);

    // ── Aperçu / téléchargement d'une pièce jointe (visualiseur intégré) ───────
    // Remplace l'ancien accès direct non authentifié via /storage/{path}.
    Route::get('document-requests/{id}/files/{source}/{key}', [DocumentRequestController::class, 'previewFile']);

    // ── Fichiers secrétaire ───────────────────────────────────────────────────
    Route::post('document-requests/{id}/secretary-files',                  [DocumentRequestController::class, 'uploadSecretaryFiles']);
    Route::patch('document-requests/{id}/secretary-files/{fileId}',        [DocumentRequestController::class, 'updateSecretaryFileComment']);
    Route::delete('document-requests/{id}/secretary-files/{fileId}',       [DocumentRequestController::class, 'deleteSecretaryFile']);

    // ── Stats direction ───────────────────────────────────────────────────────
    Route::get('document-requests/stats',  DocumentRequestStatsController::class);

    // ── Transitions workflow ──────────────────────────────────────────────────
    Route::post('document-requests/{id}/transition', DocumentRequestTransitionController::class);

    // ── Historique ────────────────────────────────────────────────────────────
    Route::get('document-requests/{id}/history', [DocumentRequestHistoryController::class, 'index']);

});