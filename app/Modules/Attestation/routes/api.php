<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Attestation\Http\Controllers\AttestationController;

Route::prefix('api/attestations')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        
        Route::get('eligible/success', [AttestationController::class, 'getEligibleForSuccess']);
        Route::get('eligible/preparatory', [AttestationController::class, 'getEligibleForPreparatory']);
        Route::post('generate/success', [AttestationController::class, 'generateSuccess']);
        Route::post('generate/preparatory', [AttestationController::class, 'generatePreparatory']);
        Route::post('generate/preparatory/multiple', [AttestationController::class, 'generateMultiplePreparatory']);
        Route::post('generate/bulletin', [AttestationController::class, 'generateBulletin']);
        Route::post('generate/bulletin/multiple', [AttestationController::class, 'generateMultipleBulletins']);
        Route::post('generate/licence', [AttestationController::class, 'generateLicence']);
        Route::post('generate/licence/multiple', [AttestationController::class, 'generateMultipleLicence']);
        Route::put('students/{studentPendingStudentId}/names', [AttestationController::class, 'updateStudentNames']);
        Route::get('students/{studentPendingStudentId}/birth-certificate', [AttestationController::class, 'getBirthCertificate']);
    });
});
