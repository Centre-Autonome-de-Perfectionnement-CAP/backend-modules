<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Demandes\Http\Controllers\DocumentRequestController;

/*
 * Module Demandes — routes gestion du workflow de documents
 *
 * Ces routes étaient précédemment dans App\Modules\Attestation\routes\api.php.
 * Elles vivent maintenant dans leur propre module, indépendant d'Attestation.
 *
 * URL de base identique : /api/attestations/document-requests
 * (inchangée côté frontend — aucun impact sur document-request.service.ts)
 */

Route::prefix('api/attestations')->middleware('auth:sanctum')->group(function () {

    Route::get('document-requests',                   [DocumentRequestController::class, 'index']);
    Route::get('document-requests/{id}',              [DocumentRequestController::class, 'show']);
    Route::post('document-requests/{id}/transition',  [DocumentRequestController::class, 'transition']);

});
