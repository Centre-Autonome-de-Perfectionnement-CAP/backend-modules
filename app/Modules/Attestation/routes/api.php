<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Attestation\Http\Controllers\AttestationController;
use App\Modules\Attestation\Http\Controllers\Public\DemandeController;
use App\Modules\Attestation\Http\Controllers\Public\QuittanceController;
use App\Modules\Attestation\Http\Controllers\Public\ComplementDossierController;

/*
 * Module Attestation — routes/api.php
 *
 * Harmonisation : ajout des routes complément de dossier (publiques, sans auth)
 *   GET  /api/attestations/demandes/complement/find
 *   POST /api/attestations/demandes/complement
 *
 * IMPORTANT : la route GET find doit être déclarée AVANT le groupe auth:sanctum
 * pour ne pas être interceptée par le middleware.
 */


Route::prefix('api/attestations')->group(function () {

    // ── Routes publiques (site vitrine) ───────────────────────────────────────
    //
    // AJOUT (audit sécurité 13/10/2026) : limitation de débit — limiteurs
    // nommés définis dans AttestationServiceProvider::boot().

    Route::middleware('throttle:attestation-public-read')->group(function () {
        Route::get('academic-years',        [AttestationController::class, 'getAcademicYears']);
        Route::get('status',                [AttestationController::class, 'getStatus']);
        Route::get('bulletin-status',       [AttestationController::class, 'getBulletinStatus']);
        Route::get('identify',              [AttestationController::class, 'identify']);
        Route::get('check-availability',    [AttestationController::class, 'checkAvailability']);
        Route::get('demandes/suivi',        [DemandeController::class, 'suiviDemande']);

        // ── Complément de dossier (public — étudiant sans compte) ─────────────
        Route::get('demandes/complement/find', [ComplementDossierController::class, 'find']);
    });

    Route::middleware('throttle:attestation-public-write')->group(function () {
        Route::post('demandes',             [DemandeController::class, 'storeDemande']);
        Route::post('bulletins',            [DemandeController::class, 'storeBulletinDemande']);
        Route::post('quittance/generate',   [QuittanceController::class, 'generateAndSendQuittance']);
        Route::post('demandes/complement',  [ComplementDossierController::class, 'store']);
        // Validation par code (WhatsApp + email enregistrés) avant le dépôt du complément
        Route::post('demandes/complement/otp',        [ComplementDossierController::class, 'sendOtp']);
        Route::post('demandes/complement/otp/verify', [ComplementDossierController::class, 'verifyOtp']);
    });

    // ── Routes protégées (progiciel interne) ──────────────────────────────────

    Route::middleware('auth:sanctum')->group(function () {

        // Éligibilité
        Route::get('eligible/passage',               [AttestationController::class, 'getEligibleForPassage']);
        Route::get('eligible/preparatory',           [AttestationController::class, 'getEligibleForPreparatory']);
        Route::get('eligible/definitive',            [AttestationController::class, 'getEligibleForDefinitive']);
        Route::get('eligible/inscription',           [AttestationController::class, 'getEligibleForInscription']);
        Route::get('eligible/bulletins',             [AttestationController::class, 'getStudentsForBulletins']); // ← ajouté sur main

        // Génération unitaire
        Route::post('generate/passage',              [AttestationController::class, 'generatePassage']);
        Route::post('generate/preparatory',          [AttestationController::class, 'generatePreparatory']);
        Route::post('generate/definitive',           [AttestationController::class, 'generateDefinitive']);
        Route::post('generate/inscription',          [AttestationController::class, 'generateInscription']);
        Route::post('generate/bulletin',             [AttestationController::class, 'generateBulletin']);
        Route::post('generate/licence',              [AttestationController::class, 'generateLicence']);

        // Génération multiple
        Route::post('generate/passage/multiple',     [AttestationController::class, 'generateMultiplePassage']);
        Route::post('generate/preparatory/multiple', [AttestationController::class, 'generateMultiplePreparatory']);
        Route::post('generate/definitive/multiple',  [AttestationController::class, 'generateMultipleDefinitive']);
        Route::post('generate/inscription/multiple', [AttestationController::class, 'generateMultipleInscription']);
        Route::post('generate/bulletin/multiple',    [AttestationController::class, 'generateMultipleBulletins']);
        Route::post('generate/licence/multiple',     [AttestationController::class, 'generateMultipleLicence']);

        // Utilitaires étudiants
        Route::put('students/{studentPendingStudentId}/names',             [AttestationController::class, 'updateStudentNames']);
        Route::get('students/{studentPendingStudentId}/birth-certificate', [AttestationController::class, 'getBirthCertificate']);
        // FUSION develop/main : la route provisoire de main
        // « document-requests/badge-count → {count: 0} » n'est pas reprise : la même
        // URI est servie par le module Demandes (DocumentRequestBadgeController,
        // compteur réel). La garder aurait masqué le vrai compteur selon l'ordre
        // de chargement des providers.
    });
});
