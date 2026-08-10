<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Demandes\Http\Controllers\DocumentRequestController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestTransitionController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestHistoryController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestStatsController;
use App\Modules\Demandes\Http\Controllers\DocumentRequestBadgeController;

/*
 * CORRECTIF (v2) — basé sur le fichier de routes réel.
 *
 * BUG RÉEL CORRIGÉ (B3.6) : dans le fichier original, la route
 *   GET document-requests/{id}
 * est déclarée AVANT
 *   GET document-requests/stats
 * et AVANT
 *   GET document-requests/badge-count
 * Comme {id} n'a pas de contrainte ->whereNumber('id'), Laravel essaie de
 * faire correspondre 'stats' et 'badge-count' au paramètre {id} EN PREMIER
 * (le routeur résout dans l'ordre de déclaration et {id} accepte n'importe
 * quelle chaîne). Concrètement : un appel à GET /document-requests/stats
 * tombe dans show(1) avec $id = 'stats', qui échoue silencieusement par un
 * 404 "Demande #stats introuvable" au lieu d'appeler DocumentRequestStatsController.
 *
 * CORRIGÉ ICI : 'stats' et 'badge-count' sont replacés AVANT '{id}',
 * et '{id}' reçoit désormais ->whereNumber('id') par sécurité supplémentaire.
 *
 * AJOUT (B3.2) : route paginée additive, ne remplace pas l'existante.
 */

Route::prefix('api/attestations')->middleware('auth:sanctum')->group(function () {

    // ── Routes à chemin fixe — DOIVENT précéder {id} ──────────────────────────
    Route::get('document-requests/stats',        DocumentRequestStatsController::class);
    Route::get('document-requests/badge-count',  DocumentRequestBadgeController::class);
    Route::get('document-requests/paginated',    [DocumentRequestController::class, 'indexPaginated']); // ← AJOUT B3.2

    // ── Listing + détail ───────────────────────────────────────────────────────
    Route::get('document-requests',              [DocumentRequestController::class, 'index']);
    Route::get('document-requests/{id}',         [DocumentRequestController::class, 'show'])
         ->whereNumber('id'); // ← AJOUT : garde-fou supplémentaire

    // ── Aperçu / téléchargement d'une pièce jointe ─────────────────────────────
    Route::get('document-requests/{id}/files/{source}/{key}', [DocumentRequestController::class, 'previewFile'])
         ->whereNumber('id');

    // ── Fichiers secrétaire ─────────────────────────────────────────────────────
    Route::post('document-requests/{id}/secretary-files',            [DocumentRequestController::class, 'uploadSecretaryFiles'])->whereNumber('id');
    Route::patch('document-requests/{id}/secretary-files/{fileId}',  [DocumentRequestController::class, 'updateSecretaryFileComment'])->whereNumber('id');
    Route::delete('document-requests/{id}/secretary-files/{fileId}',[DocumentRequestController::class, 'deleteSecretaryFile'])->whereNumber('id');

    // ── Transitions workflow ─────────────────────────────────────────────────────
    Route::post('document-requests/{id}/transition', DocumentRequestTransitionController::class)->whereNumber('id');

    // ── Historique ────────────────────────────────────────────────────────────────
    Route::get('document-requests/{id}/history', [DocumentRequestHistoryController::class, 'index'])->whereNumber('id');

});
