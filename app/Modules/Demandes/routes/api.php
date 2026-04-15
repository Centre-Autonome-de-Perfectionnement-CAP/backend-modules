<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Demandes\Http\Controllers\DocumentRequestController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestTransitionController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestHistoryController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestStatsController;

/*
 * Module Demandes — workflow de gestion des demandes de documents
 *
 * Base URL : /api/attestations   (inchangée — aucun impact côté frontend)
 */

Route::prefix('api/attestations')->middleware('auth:sanctum')->group(function () {

    // ── Listing + détail ──────────────────────────────────────────────────────
    Route::get('document-requests',        [DocumentRequestController::class, 'index']);
    Route::get('document-requests/{id}',   [DocumentRequestController::class, 'show']);

    // ── Stats direction ───────────────────────────────────────────────────────
    Route::get('document-requests/stats',  DocumentRequestStatsController::class);

    // ── Transitions workflow ──────────────────────────────────────────────────
    Route::post('document-requests/{id}/transition', DocumentRequestTransitionController::class);

    // ── Historique ────────────────────────────────────────────────────────────
    Route::get('document-requests/{id}/history',     [DocumentRequestHistoryController::class, 'index']);

});
