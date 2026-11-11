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
use App\Modules\Stages\Http\Controllers\StageChatController;
use App\Modules\Stages\Http\Controllers\StageFeedbackController;
use App\Modules\Stages\Http\Controllers\StageTaskController;
use App\Modules\Stages\Http\Controllers\WorkAttestationController;
use Illuminate\Support\Facades\Route;

/*
 * Module Stages - Lot 1.
 * Limiteurs nommés définis dans StagesServiceProvider::boot().
 */

Route::prefix('api/stages')->middleware('api')->group(function () {

    // ── Site vitrine (public) ─────────────────────────────────────────────
    Route::get('statut', [StagePublicController::class, 'status'])->middleware('throttle:stages-public-read');
    Route::post('demandes', [StagePublicController::class, 'store'])->middleware('throttle:stages-public-submit');
    Route::get('avis-publics', [StageFeedbackController::class, 'publicSummary'])->middleware('throttle:stages-public-read');

    Route::prefix('suivi')->group(function () {
        Route::post('code', [StagePublicController::class, 'sendCode'])->middleware('throttle:stages-public-code');
        Route::post('code/verifier', [StagePublicController::class, 'verifyCode'])->middleware('throttle:stages-public-code');
        Route::get('/', [StagePublicController::class, 'show'])->middleware('throttle:stages-public-read');
        Route::post('reponse', [StagePublicController::class, 'respond'])->middleware('throttle:stages-public-code');
        Route::post('binome', [StagePublicController::class, 'binomeInvite'])->middleware('throttle:stages-public-code');
        Route::delete('binome', [StagePublicController::class, 'binomeCancel'])->middleware('throttle:stages-public-code');
        Route::post('binome/{id}', [StagePublicController::class, 'binomeRespond'])->whereNumber('id')->middleware('throttle:stages-public-code');
    });

    // ── Invitation d'un superviseur (lien reçu sur WhatsApp) ─────────────
    Route::get('invitation', [SupervisorInvitationController::class, 'show'])->middleware('throttle:stages-public-code');
    Route::post('invitation/accepter', [SupervisorInvitationController::class, 'accept'])->middleware('throttle:stages-public-code');

    // ── Stagiaire arrivé avant le système : dossier complété via son lien ─
    Route::get('rejoindre', [InternInvitationController::class, 'show'])->middleware('throttle:stages-public-code');
    Route::post('rejoindre', [InternInvitationController::class, 'register'])->middleware('throttle:stages-public-code');

    // ── Attestation de travail : fiche remplie via le lien reçu (sans compte) ─
    Route::get('attestation-travail', [WorkAttestationController::class, 'publicShow'])->middleware('throttle:stages-public-code');
    Route::post('attestation-travail', [WorkAttestationController::class, 'publicSubmit'])->middleware('throttle:stages-public-code');

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

        // Attestations de travail : décisions réservées au responsable.
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('attestations-travail', [WorkAttestationController::class, 'invite']);
            Route::post('stagiaires/{id}/attestation-travail', [WorkAttestationController::class, 'fromStage'])->whereNumber('id');
            Route::put('attestations-travail/{id}', [WorkAttestationController::class, 'update'])->whereNumber('id');
            Route::post('attestations-travail/{id}/valider', [WorkAttestationController::class, 'validateWork'])->whereNumber('id');
            Route::post('attestations-travail/{id}/renvoyer', [WorkAttestationController::class, 'resend'])->whereNumber('id');
            Route::post('attestations-travail/{id}/annuler', [WorkAttestationController::class, 'cancel'])->whereNumber('id');
        });
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
            Route::post('attestations/{id}/prevenir', [AttestationController::class, 'notify'])->whereNumber('id');
            Route::post('attestations-travail/{id}/imprimee', [WorkAttestationController::class, 'printed'])->whereNumber('id');
            Route::post('attestations-travail/{id}/prevenir', [WorkAttestationController::class, 'notify'])->whereNumber('id');
            Route::post('attestations-travail/{id}/remise', [WorkAttestationController::class, 'delivered'])->whereNumber('id');
        });
        Route::get('attestations-travail', [WorkAttestationController::class, 'index']);
        Route::get('attestations-travail/{id}', [WorkAttestationController::class, 'show'])->whereNumber('id');
        Route::get('attestations-travail/{id}/pdf', [WorkAttestationController::class, 'pdf'])->whereNumber('id')->middleware('throttle:30,1');
    });

    // ── Progiciel : responsables + superviseurs ───────────────────────────
    Route::prefix('admin')->middleware(['auth:sanctum', 'can:stages.manage'])->group(function () {
        Route::post('stagiaires/superviseur', [SupervisionController::class, 'setSupervisor'])->middleware('throttle:30,1');
        Route::get('superviseurs', [SupervisionController::class, 'supervisors']);
        Route::get('stagiaires/{id}/presence', [InternFollowController::class, 'presence'])->whereNumber('id');
        Route::post('stagiaires/{id}/appareil/reinitialiser', [InternFollowController::class, 'resetDevice'])->whereNumber('id')->middleware('throttle:20,1');
        Route::post('stagiaires/{id}/presence/regulariser', [InternFollowController::class, 'regularize'])->whereNumber('id')->middleware('throttle:20,1');
        Route::get('demandes-stagiaires', [InternFollowController::class, 'requestsIndex']);
        Route::post('demandes-stagiaires/{id}/decision', [InternFollowController::class, 'decide'])->whereNumber('id')->middleware('throttle:60,1');
        Route::get('demandes-stagiaires/{id}/piece', [InternFollowController::class, 'requestFile'])->whereNumber('id');
        Route::get('stagiaires/{id}/rapports', [ReportController::class, 'forIntern'])->whereNumber('id');
        Route::get('stagiaires/{id}/attestation', [AttestationController::class, 'forStage'])->whereNumber('id');
        Route::middleware('throttle:20,1')->group(function () {
            Route::post('demandes/{id}/fin', [StageAdminController::class, 'endStage'])->whereNumber('id');
            Route::post('demandes/{id}/fin/annuler', [StageAdminController::class, 'undoEnd'])->whereNumber('id');
            Route::post('demandes/{id}/retablir', [StageAdminController::class, 'reinstate'])->whereNumber('id');
        });
        Route::get('messages', [StageChatController::class, 'index'])->middleware('throttle:120,1');
        Route::get('messages/{channel}', [StageChatController::class, 'show'])->where('channel', StageChatController::CHANNEL)->middleware('throttle:120,1');
        Route::post('messages/{channel}', [StageChatController::class, 'store'])->where('channel', StageChatController::CHANNEL)->middleware('throttle:30,1');
        Route::patch('messages/{channel}/{message}', [StageChatController::class, 'update'])->where('channel', StageChatController::CHANNEL)->whereNumber('message')->middleware('throttle:30,1');
        Route::get('messages/{channel}/fichiers/{file}', [StageChatController::class, 'file'])->where('channel', StageChatController::CHANNEL)->whereNumber('file')->middleware('throttle:120,1');
        Route::get('taches', [StageTaskController::class, 'index']);
        Route::get('taches/publies', [StageTaskController::class, 'published']);
        Route::get('taches/invitables', [StageTaskController::class, 'invitables']);
        Route::get('taches/{id}', [StageTaskController::class, 'show'])->whereNumber('id');
        Route::get('taches/{id}/fichiers/{item}', [StageTaskController::class, 'file'])->whereNumber(['id', 'item'])->middleware('throttle:120,1');

        // Documents et références, avis, notation, évaluation des stagiaires
        Route::get('documents', [StageFeedbackController::class, 'documents']);
        Route::get('documents/{id}/fichier', [StageFeedbackController::class, 'documentFile'])->whereNumber('id')->middleware('throttle:120,1');
        Route::get('avis', [StageFeedbackController::class, 'feedbackIndex']);
        Route::get('avis/compteur', [StageFeedbackController::class, 'feedbackCount']);
        Route::get('avis/{id}/fichier', [StageFeedbackController::class, 'feedbackFile'])->whereNumber('id');
        Route::get('notation', [StageFeedbackController::class, 'ratings']);
        Route::get('evaluations', [StageFeedbackController::class, 'evaluations']);
        Route::get('evaluations/suivi', [StageFeedbackController::class, 'evaluationBoard']);
        Route::get('evaluations/{id}/pdf', [StageFeedbackController::class, 'evaluationPdf'])->whereNumber('id');
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('documents', [StageFeedbackController::class, 'documentStore']);
            Route::delete('documents/{id}', [StageFeedbackController::class, 'documentDestroy'])->whereNumber('id');
            Route::post('avis/{id}/publication', [StageFeedbackController::class, 'feedbackPublish'])->whereNumber('id');
            Route::delete('avis/{id}', [StageFeedbackController::class, 'feedbackDestroy'])->whereNumber('id');
            Route::post('evaluations/{id}', [StageFeedbackController::class, 'evaluationSave'])->whereNumber('id');
            Route::post('evaluations/{id}/renvoi', [StageFeedbackController::class, 'evaluationReturn'])->whereNumber('id');
            Route::post('evaluations/{id}/validation', [StageFeedbackController::class, 'evaluationValidate'])->whereNumber('id');
        });
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('taches', [StageTaskController::class, 'store']);
            Route::delete('taches/{id}', [StageTaskController::class, 'destroy'])->whereNumber('id');
            Route::post('taches/{id}/decision', [StageTaskController::class, 'decide'])->whereNumber('id');
            Route::post('taches/{id}/publication', [StageTaskController::class, 'publish'])->whereNumber('id');
            Route::post('taches/{id}/invitations', [StageTaskController::class, 'invite'])->whereNumber('id');
            Route::delete('taches/{id}/invitations/{type}/{who}', [StageTaskController::class, 'uninvite'])->whereNumber(['id', 'who'])->whereIn('type', ['intern', 'staff']);
        });
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
        Route::delete('rapports/{id}/commentaires/{comment}', [ReportController::class, 'deleteComment'])->whereNumber(['id', 'comment'])->middleware('throttle:30,1');
        Route::post('rapports/{id}/decision', [ReportController::class, 'decide'])->whereNumber('id')->middleware('throttle:30,1');
        Route::get('equipes', [SupervisionController::class, 'teams']);
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('equipes', [SupervisionController::class, 'saveTeam']);
            Route::delete('equipes/{id}', [SupervisionController::class, 'deleteTeam'])->whereNumber('id');
            Route::patch('equipes/{id}/rapport-final', [SupervisionController::class, 'teamFinalMode'])->whereNumber('id');
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
            Route::post('demandes/{id}/notes/{noteId}/renvoyer', [StageAdminController::class, 'resendNote'])->whereNumber(['id', 'noteId']);
        });
    });
});

// ── Espace stagiaire (site vitrine) ──────────────────────────────────────
// Sessions propres (en-tête X-Stagiaire-Token), sans lien avec les comptes
// du personnel.
Route::prefix('api/stagiaire')->middleware('api')->group(function () {
    Route::middleware('throttle:stages-intern-auth')->group(function () {
        Route::get('activation', [InternSpaceController::class, 'activationInfo']);
        Route::post('activation/appareil', [InternSpaceController::class, 'activationDevice']);
        Route::post('activation/code', [InternSpaceController::class, 'activationCode']);
        Route::post('activation', [InternSpaceController::class, 'activate']);
        Route::post('connexion', [InternSpaceController::class, 'login']);
        Route::post('connexion/appareil', [InternSpaceController::class, 'confirmDevice']);
        Route::post('mot-de-passe/code', [InternSpaceController::class, 'resetCode']);
        Route::post('mot-de-passe', [InternSpaceController::class, 'resetPassword']);
    });

    Route::middleware([AuthenticateIntern::class, 'throttle:stages-intern'])->group(function () {
        Route::post('deconnexion', [InternSpaceController::class, 'logout']);
        Route::post('fin-de-stage', [InternSpaceController::class, 'endStage'])->middleware('throttle:stages-intern-write');
        Route::get('messages', [StageChatController::class, 'index']);
        Route::get('messages/{channel}', [StageChatController::class, 'show'])->where('channel', StageChatController::CHANNEL);
        Route::post('messages/{channel}', [StageChatController::class, 'store'])->where('channel', StageChatController::CHANNEL)->middleware('throttle:stages-intern-chat');
        Route::patch('messages/{channel}/{message}', [StageChatController::class, 'update'])->where('channel', StageChatController::CHANNEL)->whereNumber('message')->middleware('throttle:stages-intern-chat');
        Route::get('messages/{channel}/fichiers/{file}', [StageChatController::class, 'file'])->where('channel', StageChatController::CHANNEL)->whereNumber('file');
        Route::get('moi', [InternSpaceController::class, 'me']);
        Route::post('reglement', [InternSpaceController::class, 'acceptReglement']);
        Route::post('empreinte/options', [InternSpaceController::class, 'webauthnRegisterOptions']);
        Route::post('empreinte', [InternSpaceController::class, 'webauthnRegister']);
        Route::post('code', [InternSpaceController::class, 'code'])->middleware('throttle:stages-intern-medium');
        Route::post('empreinte/code', [InternSpaceController::class, 'codeRegister'])->middleware('throttle:stages-intern-medium');
        Route::post('pointage/options', [InternSpaceController::class, 'punchOptions'])->middleware('throttle:stages-intern-medium');
        Route::post('pointage', [InternSpaceController::class, 'punch'])->middleware('throttle:stages-intern-medium');
        Route::get('presences', [InternSpaceController::class, 'attendance']);
        Route::get('demandes', [InternSpaceController::class, 'requestsIndex']);
        Route::post('demandes', [InternSpaceController::class, 'requestsStore'])->middleware('throttle:stages-intern-write');
        Route::delete('demandes/{id}', [InternSpaceController::class, 'requestsCancel'])->whereNumber('id');
        Route::get('rapports', [InternSpaceController::class, 'reportsIndex']);
        Route::get('rapports/tous', [InternSpaceController::class, 'reportsBoard']);
        Route::post('rapports', [InternSpaceController::class, 'reportsStore'])->middleware('throttle:stages-intern-write');
        Route::get('rapports/{id}', [InternSpaceController::class, 'reportShow'])->whereNumber('id');
        Route::post('rapports/{id}/commentaires', [InternSpaceController::class, 'reportComment'])->whereNumber('id')->middleware('throttle:stages-intern-medium');
        Route::delete('rapports/{id}/commentaires/{comment}', [InternSpaceController::class, 'reportCommentDelete'])->whereNumber(['id', 'comment'])->middleware('throttle:stages-intern-medium');
        Route::get('rapports/{id}/versions/{version}', [InternSpaceController::class, 'reportFile'])->whereNumber(['id', 'version']);
        Route::get('taches', [StageTaskController::class, 'index']);
        Route::get('taches/publies', [StageTaskController::class, 'published']);
        Route::get('taches/invitables', [StageTaskController::class, 'invitables']);
        Route::get('taches/{id}', [StageTaskController::class, 'show'])->whereNumber('id');
        Route::get('taches/{id}/fichiers/{item}', [StageTaskController::class, 'file'])->whereNumber(['id', 'item']);
        Route::middleware('throttle:stages-intern-write')->group(function () {
            Route::post('taches', [StageTaskController::class, 'store']);
            Route::delete('taches/{id}', [StageTaskController::class, 'destroy'])->whereNumber('id');
            Route::post('taches/{id}/remise', [StageTaskController::class, 'submit'])->whereNumber('id');
            Route::post('taches/{id}/invitations', [StageTaskController::class, 'invite'])->whereNumber('id');
            Route::delete('taches/{id}/invitations/{type}/{who}', [StageTaskController::class, 'uninvite'])->whereNumber(['id', 'who'])->whereIn('type', ['intern', 'staff']);
        });
        Route::get('documents', [StageFeedbackController::class, 'documents']);
        Route::get('documents/{id}/fichier', [StageFeedbackController::class, 'documentFile'])->whereNumber('id')->middleware('throttle:stages-intern-medium');
        Route::post('avis', [StageFeedbackController::class, 'feedbackStore'])->middleware('throttle:stages-intern-write');
        Route::get('notation', [StageFeedbackController::class, 'ratings']);
        Route::post('notation', [StageFeedbackController::class, 'rate'])->middleware('throttle:stages-intern-write');
        Route::get('evaluation', [StageFeedbackController::class, 'myEvaluation']);
        Route::get('evaluation/pdf', [StageFeedbackController::class, 'evaluationPdf'])->middleware('throttle:stages-intern-medium');
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
