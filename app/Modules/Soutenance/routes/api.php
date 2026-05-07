<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Soutenance\Http\Controllers\DefenseSubmissionController;
use App\Modules\Soutenance\Http\Controllers\DefensePdfController;
use App\Modules\Soutenance\Http\Controllers\DefenseJuryController;

use App\Modules\Soutenance\Http\Controllers\DefenseSubmissionPeriodController;


// Routes publiques
Route::prefix('soutenance')->group(function () {
    Route::post('/submissions', [DefenseSubmissionController::class, 'store']);
    Route::post('/validation_qui', [DefensePdfController::class, 'generateQuitus'])->name('qunewpost');
    Route::post('/correction_post', [DefensePdfController::class, 'generateCorrection'])->name('correction_post');
    Route::get('/periods/active', [DefenseSubmissionPeriodController::class, 'getActivePeriod']);
});

// Routes protégées
Route::prefix('soutenance')->middleware('auth:sanctum')->group(function () {
    // Périodes de soumission
    Route::get('/periods', [DefenseSubmissionPeriodController::class, 'index']);
    Route::post('/periods', [DefenseSubmissionPeriodController::class, 'store']);
    Route::put('/periods/{id}', [DefenseSubmissionPeriodController::class, 'update']);
    Route::delete('/periods/{id}', [DefenseSubmissionPeriodController::class, 'destroy']);

    // Soumissions de soutenance
    Route::get('/submissions', [DefenseSubmissionController::class, 'index']);
    Route::get('/submissions/data', [DefenseSubmissionController::class, 'getData']);
    Route::post('/submissions/accept', [DefenseSubmissionController::class, 'accept']);
    Route::post('/submissions/reject', [DefenseSubmissionController::class, 'reject']);
    Route::get('/submissions/{id}', [DefenseSubmissionController::class, 'show']);
    Route::get('/submissions/{id}/dossier', [DefenseSubmissionController::class, 'getDossierDetails']);
    Route::put('/submissions/{id}/status', [DefenseSubmissionController::class, 'updatestatus']);
    Route::put('/submissions/{id}/schedule', [DefenseSubmissionController::class, 'scheduleDefense']);
    Route::delete('/submissions/{id}', [DefenseSubmissionController::class, 'destroy']);
    Route::get('/statistics', [DefenseSubmissionController::class, 'statistics']);

    // Membres du jury
    Route::get('/jury', [DefenseJuryController::class, 'index']);
    Route::get('/jury/data', [DefenseJuryController::class, 'getData']);
    Route::get('/jury/get', [DefenseJuryController::class, 'getJury']);
    Route::get('/jury/suggestions', [DefenseJuryController::class, 'getSuggestions']);
    Route::get('/jury/check-status', [DefenseJuryController::class, 'checkstatus']);
    Route::get('/submissions/{submissionId}/jury', [DefenseJuryController::class, 'index']);
    Route::post('/submissions/{submissionId}/jury', [DefenseJuryController::class, 'store']);
    Route::put('/submissions/{submissionId}/jury/{juryMemberId}', [DefenseJuryController::class, 'update']);
    Route::delete('/submissions/{submissionId}/jury/{juryMemberId}', [DefenseJuryController::class, 'destroy']);
});
