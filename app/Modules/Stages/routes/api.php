<?php

use App\Modules\Stages\Http\Controllers\Public\StagePublicController;
use App\Modules\Stages\Http\Controllers\Public\SupervisorInvitationController;
use App\Modules\Stages\Http\Controllers\SupervisionController;
use App\Modules\Stages\Http\Controllers\InternFollowController;
use App\Modules\Stages\Http\Controllers\Public\InternSpaceController;
use App\Modules\Stages\Http\Middleware\AuthenticateIntern;
use App\Modules\Stages\Http\Controllers\StageAdminController;
use Illuminate\Support\Facades\Route;

/*
 * Module Stages — Lot 1.
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

    // ── Progiciel : responsables (admin, Responsable de division FC) ──────
    Route::prefix('admin')->middleware(['auth:sanctum', 'can:stages.lead'])->group(function () {
        Route::get('reglages', [StageAdminController::class, 'getSettings']);
        Route::put('reglages', [StageAdminController::class, 'updateSettings'])->middleware('throttle:30,1');

        Route::get('invitations', [SupervisionController::class, 'invitationsIndex']);
        Route::middleware('throttle:20,1')->group(function () {
            Route::post('invitations', [SupervisionController::class, 'invite']);
            Route::post('invitations/{id}/renvoyer', [SupervisionController::class, 'resendInvitation'])->whereNumber('id');
            Route::delete('invitations/{id}', [SupervisionController::class, 'revokeInvitation'])->whereNumber('id');
        });

        Route::get('groupe-whatsapp', [SupervisionController::class, 'whatsappGroup']);
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('groupe-whatsapp/creer', [SupervisionController::class, 'createWhatsappGroup']);
            Route::post('groupe-whatsapp/lier', [SupervisionController::class, 'linkWhatsappGroup']);
            Route::post('groupe-whatsapp/synchroniser', [SupervisionController::class, 'syncWhatsappGroup']);
            Route::post('groupe-whatsapp/{id}/dissoudre', [SupervisionController::class, 'dissolveWhatsappGroup'])->whereNumber('id');
        });
    });

    // ── Progiciel : responsables + superviseurs ───────────────────────────
    Route::prefix('admin')->middleware(['auth:sanctum', 'can:stages.manage'])->group(function () {
        Route::get('stagiaires', [SupervisionController::class, 'interns']);
        Route::post('stagiaires/superviseur', [SupervisionController::class, 'setSupervisor'])->middleware('throttle:30,1');
        Route::get('superviseurs', [SupervisionController::class, 'supervisors']);
        Route::get('stagiaires/{id}/presence', [InternFollowController::class, 'presence'])->whereNumber('id');
        Route::post('stagiaires/{id}/appareil/reinitialiser', [InternFollowController::class, 'resetDevice'])->whereNumber('id')->middleware('throttle:20,1');
        Route::get('demandes-stagiaires', [InternFollowController::class, 'requestsIndex']);
        Route::post('demandes-stagiaires/{id}/decision', [InternFollowController::class, 'decide'])->whereNumber('id')->middleware('throttle:60,1');
        Route::get('demandes-stagiaires/{id}/piece', [InternFollowController::class, 'requestFile'])->whereNumber('id');
        Route::get('equipes', [SupervisionController::class, 'teams']);
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('equipes', [SupervisionController::class, 'saveTeam']);
            Route::put('equipes/{id}', [SupervisionController::class, 'saveTeam'])->whereNumber('id');
            Route::delete('equipes/{id}', [SupervisionController::class, 'deleteTeam'])->whereNumber('id');
        });

        Route::get('demandes', [StageAdminController::class, 'index']);
        Route::get('demandes/{id}', [StageAdminController::class, 'show'])->where('id', '[A-Za-z0-9\-]+');
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

    Route::middleware([AuthenticateIntern::class, 'throttle:120,1'])->group(function () {
        Route::post('deconnexion', [InternSpaceController::class, 'logout']);
        Route::get('moi', [InternSpaceController::class, 'me']);
        Route::post('reglement', [InternSpaceController::class, 'acceptReglement']);
        Route::post('empreinte/options', [InternSpaceController::class, 'webauthnRegisterOptions']);
        Route::post('empreinte', [InternSpaceController::class, 'webauthnRegister']);
        Route::post('pointage/options', [InternSpaceController::class, 'punchOptions'])->middleware('throttle:20,1');
        Route::post('pointage', [InternSpaceController::class, 'punch'])->middleware('throttle:20,1');
        Route::get('presences', [InternSpaceController::class, 'attendance']);
        Route::get('demandes', [InternSpaceController::class, 'requestsIndex']);
        Route::post('demandes', [InternSpaceController::class, 'requestsStore'])->middleware('throttle:10,1');
        Route::delete('demandes/{id}', [InternSpaceController::class, 'requestsCancel'])->whereNumber('id');
    });
});
