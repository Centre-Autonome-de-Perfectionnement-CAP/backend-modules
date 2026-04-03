<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Attendance\Http\Controllers\AttendanceController;

Route::prefix('attendance')->group(function () {

    // Filtres dynamiques
    Route::get('/filters', [AttendanceController::class, 'getFilters']);

    // Dashboard
    Route::get('/dashboard', [AttendanceController::class, 'dashboard']);

    // Management + Export
    Route::get('/management',        [AttendanceController::class, 'management']);
    Route::get('/management/export', [AttendanceController::class, 'export']);
    // ?format=pdf|excel|word  +  ?year=&filiere=&niveau=&matiere=&salle=

    // Fingerprint CRUD + Export
    Route::get('/fingerprint',         [AttendanceController::class, 'fingerprint']);
    Route::post('/fingerprint',        [AttendanceController::class, 'storeFingerprint']);
    Route::put('/fingerprint/{id}',    [AttendanceController::class, 'updateFingerprint']);
    Route::delete('/fingerprint/{id}', [AttendanceController::class, 'deleteFingerprint']);
    Route::get('/fingerprint/export',  [AttendanceController::class, 'exportFingerprint']);
    // ?format=pdf|excel|word  +  ?annee=&filiere=&niveau=

    // Scan présence
    Route::post('/scan', [AttendanceController::class, 'scan']);
});
