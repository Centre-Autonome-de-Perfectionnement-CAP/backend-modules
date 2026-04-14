<?php

use App\Modules\Demandes\Http\Controllers\DocumentRequestController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestHistoryController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestStatsController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestTransitionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Module Demandes — Routes API
|--------------------------------------------------------------------------
|
| Découpage :
|   DocumentRequestController           → GET  index, show
|   DocumentRequestTransitionController → POST transition (workflow + flag ack)
|   DocumentRequestHistoryController    → GET  history
|   DocumentRequestStatsController      → GET  stats/direction
|
| ⚠ La route stats/direction doit être déclarée AVANT /{id}
|   pour ne pas être capturée comme un ID numérique.
|
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // ── Stats (avant /{id} pour éviter les collisions de routing) ────────────
    Route::get('/document-requests/stats/direction', [DocumentRequestStatsController::class, 'direction']);

    // ── Lecture ───────────────────────────────────────────────────────────────
    Route::get('/document-requests',              [DocumentRequestController::class, 'index']);
    Route::get('/document-requests/{id}',         [DocumentRequestController::class, 'show']);

    // ── Mutations (workflow + acknowledge flag) ────────────────────────────────
    Route::post('/document-requests/{id}/transition', [DocumentRequestTransitionController::class, 'transition']);

    // ── Historique ────────────────────────────────────────────────────────────
    Route::get('/document-requests/{id}/history',     [DocumentRequestHistoryController::class, 'index']);

});
