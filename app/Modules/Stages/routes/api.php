<?php

use App\Modules\Stages\Http\Controllers\Public\StagePublicController;
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

    // ── Progiciel (Responsable de division formation continue + admin) ────
    Route::prefix('admin')->middleware(['auth:sanctum', 'can:stages.manage'])->group(function () {
        Route::get('reglages', [StageAdminController::class, 'getSettings']);
        Route::put('reglages', [StageAdminController::class, 'updateSettings'])->middleware('throttle:30,1');

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
