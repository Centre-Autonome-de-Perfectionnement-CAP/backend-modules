<?php

use App\Modules\Demandes\Http\Controllers\DocumentRequestController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestHistoryController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestTransitionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Module Demandes — Routes API
|--------------------------------------------------------------------------
|
| Préfixe inchangé : /api/attestations/document-requests
| → Aucun impact sur le frontend existant (document-request.service.ts).
|
| Découpage :
|   DocumentRequestController           → lecture (index, show)
|   DocumentRequestTransitionController → mutations (transition)
|   DocumentRequestHistoryController    → historique (index)
|
*/

Route::prefix('api/attestations')->middleware('auth:sanctum')->group(function () {

    // ── Lecture ──────────────────────────────────────────────────────────────
    Route::get('document-requests',       [DocumentRequestController::class, 'index']);
    Route::get('document-requests/{id}',  [DocumentRequestController::class, 'show']);

    // ── Mutations (workflow) ──────────────────────────────────────────────────
    Route::post('document-requests/{id}/transition', [DocumentRequestTransitionController::class, 'transition']);

    // ── Historique ── NOUVEAU ─────────────────────────────────────────────────
    Route::get('document-requests/{id}/history', [DocumentRequestHistoryController::class, 'index']);

});
