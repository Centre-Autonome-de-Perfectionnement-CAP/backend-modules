<?php

use App\Modules\Stages\Http\Controllers\Public\StagePublicController;
use App\Modules\Stages\Http\Controllers\Public\SupervisorInvitationController;
use App\Modules\Stages\Http\Controllers\SupervisionController;
use App\Modules\Stages\Http\Controllers\InternFollowController;
use App\Modules\Stages\Http\Controllers\InternInvitationController;
use App\Modules\Stages\Http\Controllers\ReportController;
use App\Modules\Stages\Http\Controllers\AttestationController;
use App\Modules\Stages\Http\Controllers\DashboardController;
use App\Modules\Stages\Http\Controllers\Public\InternSpaceController;
use App\Modules\Stages\Http\Middleware\AuthenticateIntern;
use App\Modules\Stages\Http\Controllers\StageAdminController;
use Illuminate\Support\Facades\Route;

/*
 * Module Stages - Lot 1.
 * Limiteurs nommés définis dans StagesServiceProvider::boot().
 */

Route::prefix('api/stages')->middleware('api')->group(function () {

    // ── Site vitrine (public) ─────────────────────────────────────────────
    Route::get('statut', [StagePublicController::class, 'status'])->middleware('throttle:stages-public-read');
    Route::post('demandes', [StagePublicController::class, 'store'])->middleware('throttle:stages-public-submit');

    Route::prefix('suivi')->group(function () {
        Route::post('code', [StagePublicController::class, 'sendCode'])->middleware('throttle:stages-public-code');
        Route::post('code/verifier', [StagePublicController::class, 'verifyCode'])->middleware('throttle:stages-public-code');
        Route::get('/', [StagePublicController::class, 'show'])->middleware('throttle:stages-public-read');
        Route::post('reponse', [StagePublicController::class, 'respond'])->middleware('throttle:stages-public-code');
    });

    // ── Invitation d'un superviseur (lien reçu sur WhatsApp) ─────────────
    Route::get('invitation', [SupervisorInvitationController::class, 'show'])->middleware('throttle:stages-public-code');
    Route::post('invitation/accepter', [SupervisorInvitationController::class, 'accept'])->middleware('throttle:stages-public-code');

    // ── Stagiaire arrivé avant le système : dossier complété via son lien ─
    Route::get('rejoindre', [InternInvitationController::class, 'show'])->middleware('throttle:stages-public-code');
    Route::post('rejoindre', [InternInvitationController::class, 'register'])->middleware('throttle:stages-public-code');

    // ── Progiciel : responsables (admin, Responsable de division FC) ──────
    Route::prefix('admin')->middleware(['auth:sanctum', 'can:stages.lead'])->group(function () {
        Route::get('reglages', [StageAdminController::class, 'getSettings']);
        Route::put('reglages', [StageAdminController::class, 'updateSettings'])->middleware('throttle:30,1');
        Route::get('reglages/signature', [StageAdminController::class, 'signatureImage']);
        Route::post('reglages/signature', [StageAdminController::class, 'uploadSignature'])->middleware('throttle:10,1');
        Route::delete('reglages/signature', [StageAdminController::class, 'deleteSignature'])->middleware('throttle:10,1');

        Route::get('invitations', [SupervisionController::class, 'invitationsIndex']);
        Route::middleware('throttle:20,1')->group(function () {
            Route::post('invitations', [SupervisionController::class, 'invite']);
            Route::post('invitations/{id}/renvoyer', [SupervisionController::class, 'resendInvitation'])->whereNumber('id');
            Route::delete('invitations/{id}', [SupervisionController::class, 'revokeInvitation'])->whereNumber('id');
        });

        // Stagiaires arrivés avant le système (invitation par numéro WhatsApp)
        Route::get('invitations-stagiaires', [InternInvitationController::class, 'index']);
        Route::middleware('throttle:20,1')->group(function () {
            Route::post('invitations-stagiaires', [InternInvitationController::class, 'invite']);
            Route::post('invitations-stagiaires/{id}/renvoyer', [InternInvitationController::class, 'resend'])->whereNumber('id');
            Route::delete('invitations-stagiaires/{id}', [InternInvitationController::class, 'revoke'])->whereNumber('id');
        });

        Route::get('groupe-whatsapp', [SupervisionController::class, 'whatsappGroup']);
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('groupe-whatsapp/creer', [SupervisionController::class, 'createWhatsappGroup']);
            Route::post('groupe-whatsapp/lier', [SupervisionController::class, 'linkWhatsappGroup']);
            Route::post('groupe-whatsapp/synchroniser', [SupervisionController::class, 'syncWhatsappGroup']);
            Route::post('groupe-whatsapp/{id}/dissoudre', [SupervisionController::class, 'dissolveWhatsappGroup'])->whereNumber('id');
        });

        // Attestations : décisions réservées au responsable.
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('stagiaires/{id}/attestation', [AttestationController::class, 'prepare'])->whereNumber('id');
            Route::put('attestations/{id}', [AttestationController::class, 'update'])->whereNumber('id');
            Route::post('attestations/{id}/valider', [AttestationController::class, 'validateAttestation'])->whereNumber('id');
            Route::post('attestations/{id}/annuler', [AttestationController::class, 'cancel'])->whereNumber('id');
        });
        Route::post('stagiaires/{id}/attestation-exceptionnelle', [AttestationController::class, 'exceptional'])
            ->whereNumber('id')->middleware('throttle:5,1');
        Route::get('exports/demandes', [DashboardController::class, 'exportDemandes'])->middleware('throttle:10,1');
    });

    // ── Progiciel : attestations (responsables + secrétariat) ─────────────
    Route::prefix('admin')->middleware(['auth:sanctum', 'can:stages.attestations'])->group(function () {
        Route::get('attestations', [AttestationController::class, 'index']);
        Route::get('attestations/{id}', [AttestationController::class, 'show'])->whereNumber('id');
        Route::get('attestations/{id}/scan', [AttestationController::class, 'scanFile'])->whereNumber('id');
        Route::get('attestations/{id}/pdf', [AttestationController::class, 'pdf'])->whereNumber('id')->middleware('throttle:30,1');
        Route::get('exports/attestations', [DashboardController::class, 'exportAttestations'])->middleware('throttle:10,1');
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('attestations/{id}/imprimee', [AttestationController::class, 'printed'])->whereNumber('id');
            Route::post('attestations/{id}/remise', [AttestationController::class, 'delivered'])->whereNumber('id');
            Route::post('attestations/{id}/scan', [AttestationController::class, 'scan'])->whereNumber('id');
        });
    });

    // ── Progiciel : responsables + superviseurs ───────────────────────────
    Route::prefix('admin')->middleware(['auth:sanctum', 'can:stages.manage'])->group(function () {
        Route::post('stagiaires/superviseur', [SupervisionController::class, 'setSupervisor'])->middleware('throttle:30,1');
        Route::get('superviseurs', [SupervisionController::class, 'supervisors']);
        Route::get('stagiaires/{id}/presence', [InternFollowController::class, 'presence'])->whereNumber('id');
        Route::post('stagiaires/{id}/appareil/reinitialiser', [InternFollowController::class, 'resetDevice'])->whereNumber('id')->middleware('throttle:20,1');
        Route::get('demandes-stagiaires', [InternFollowController::class, 'requestsIndex']);
        Route::post('demandes-stagiaires/{id}/decision', [InternFollowController::class, 'decide'])->whereNumber('id')->middleware('throttle:60,1');
        Route::get('demandes-stagiaires/{id}/piece', [InternFollowController::class, 'requestFile'])->whereNumber('id');
        Route::get('stagiaires/{id}/rapports', [ReportController::class, 'forIntern'])->whereNumber('id');
        Route::get('stagiaires/{id}/attestation', [AttestationController::class, 'forStage'])->whereNumber('id');
        Route::get('tableau-de-bord', [DashboardController::class, 'overview']);
        Route::get('suivi', [DashboardController::class, 'follow']);
        Route::middleware('throttle:10,1')->group(function () {
            Route::get('exports/stagiaires', [DashboardController::class, 'exportInterns']);
            Route::get('exports/stagiaires/{id}/presences', [DashboardController::class, 'exportPresences'])->whereNumber('id');
        });
        Route::get('rapports', [ReportController::class, 'index']);
        Route::get('rapports/{id}', [ReportController::class, 'show'])->whereNumber('id');
        Route::get('rapports/{id}/versions/{version}', [ReportController::class, 'file'])->whereNumber(['id', 'version']);
        Route::post('rapports/{id}/commentaires', [ReportController::class, 'comment'])->whereNumber('id')->middleware('throttle:30,1');
        Route::post('rapports/{id}/decision', [ReportController::class, 'decide'])->whereNumber('id')->middleware('throttle:30,1');
        Route::get('equipes', [SupervisionController::class, 'teams']);
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('equipes', [SupervisionController::class, 'saveTeam']);
            Route::delete('equipes/{id}', [SupervisionController::class, 'deleteTeam'])->whereNumber('id');
        });

        Route::get('demandes', [StageAdminController::class, 'index']);
        Route::get('demandes/{id}', [StageAdminController::class, 'show'])->where('id', '[A-Za-z0-9\-]+');
        Route::get('demandes/{id}/notes/{noteId}/pdf', [StageAdminController::class, 'notePdf'])
            ->whereNumber(['id', 'noteId'])->middleware('throttle:30,1');
        Route::get('demandes/{id}/pieces/{key}', [StageAdminController::class, 'file'])
            ->where(['id' => '[0-9]+', 'key' => '[a-z_]+']);

        Route::middleware('throttle:30,1')->group(function () {
            Route::post('demandes/{id}/proposition', [StageAdminController::class, 'propose'])->whereNumber('id');
            Route::post('demandes/{id}/accepter-contre-proposition', [StageAdminController::class, 'acceptCounter'])->whereNumber('id');
            Route::post('demandes/{id}/rejet', [StageAdminController::class, 'reject'])->whereNumber('id');
            Route::post('demandes/{id}/rectification', [StageAdminController::class, 'rectify'])->whereNumber('id');
        });
    });
});

// ── Espace stagiaire (site vitrine) ──────────────────────────────────────
// Sessions propres (en-tête X-Stagiaire-Token), sans lien avec les comptes
// du personnel.
Route::prefix('api/stagiaire')->middleware('api')->group(function () {
    Route::middleware('throttle:stages-public-code')->group(function () {
        Route::get('activation', [InternSpaceController::class, 'activationInfo']);
        Route::post('activation', [InternSpaceController::class, 'activate']);
        Route::post('connexion', [InternSpaceController::class, 'login']);
        Route::post('mot-de-passe/code', [InternSpaceController::class, 'resetCode']);
        Route::post('mot-de-passe', [InternSpaceController::class, 'resetPassword']);
    });

    Route::middleware([AuthenticateIntern::class, 'throttle:stages-intern'])->group(function () {
        Route::post('deconnexion', [InternSpaceController::class, 'logout']);
        Route::get('moi', [InternSpaceController::class, 'me']);
        Route::post('reglement', [InternSpaceController::class, 'acceptReglement']);
        Route::post('empreinte/options', [InternSpaceController::class, 'webauthnRegisterOptions']);
        Route::post('empreinte', [InternSpaceController::class, 'webauthnRegister']);
        Route::post('pointage/options', [InternSpaceController::class, 'punchOptions'])->middleware('throttle:stages-intern-medium');
        Route::post('pointage', [InternSpaceController::class, 'punch'])->middleware('throttle:stages-intern-medium');
        Route::get('presences', [InternSpaceController::class, 'attendance']);
        Route::get('demandes', [InternSpaceController::class, 'requestsIndex']);
        Route::post('demandes', [InternSpaceController::class, 'requestsStore'])->middleware('throttle:stages-intern-write');
        Route::delete('demandes/{id}', [InternSpaceController::class, 'requestsCancel'])->whereNumber('id');
        Route::get('rapports', [InternSpaceController::class, 'reportsIndex']);
        Route::post('rapports', [InternSpaceController::class, 'reportsStore'])->middleware('throttle:stages-intern-write');
        Route::get('rapports/{id}', [InternSpaceController::class, 'reportShow'])->whereNumber('id');
        Route::post('rapports/{id}/commentaires', [InternSpaceController::class, 'reportComment'])->whereNumber('id')->middleware('throttle:stages-intern-medium');
        Route::get('rapports/{id}/versions/{version}', [InternSpaceController::class, 'reportFile'])->whereNumber(['id', 'version']);
        Route::get('notes/{id}/pdf', [InternSpaceController::class, 'notePdf'])->whereNumber('id')->middleware('throttle:stages-intern-medium');
        Route::get('attestation', [InternSpaceController::class, 'attestation']);
        Route::post('attestation', [InternSpaceController::class, 'attestationRequest'])->middleware('throttle:stages-intern-write');
        Route::get('binome', [InternSpaceController::class, 'binome']);
        Route::middleware('throttle:stages-intern-write')->group(function () {
            Route::post('binome/proposition', [InternSpaceController::class, 'binomeInvite']);
            Route::delete('binome/proposition', [InternSpaceController::class, 'binomeCancel']);
            Route::post('binome/propositions/{id}/reponse', [InternSpaceController::class, 'binomeRespond'])->whereNumber('id');
        });
    });
});
