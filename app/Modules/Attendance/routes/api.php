<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Attendance\Http\Controllers\AttendanceController;

Route::prefix('attendance')->group(function () {

    // Filtres dynamiques
    Route::get('/filters',    [AttendanceController::class, 'getFilters']);

    // Dashboard
    Route::get('/dashboard',  [AttendanceController::class, 'dashboard']);

    // Management (liste globale)
    Route::get('/management',        [AttendanceController::class, 'management']);
    Route::get('/management/export', [AttendanceController::class, 'export']);

    // Séances disponibles pour un cours
    Route::get('/sessions', [AttendanceController::class, 'sessions']);

    // Présence par cours / séance
    Route::get('/course-attendance',        [AttendanceController::class, 'courseAttendance']);
    Route::get('/course-attendance/export', [AttendanceController::class, 'exportCourseAttendance']);

    // Statut capteur en temps réel
    Route::get('/sensor-status', [AttendanceController::class, 'sensorStatus']);

    // Fingerprint — IMPORTANT : /fingerprint/export AVANT /fingerprint/{id}
    Route::get('/fingerprint',         [AttendanceController::class, 'fingerprint']);
    Route::post('/fingerprint',        [AttendanceController::class, 'storeFingerprint']);
    Route::get('/fingerprint/export',  [AttendanceController::class, 'exportFingerprint']);
    Route::put('/fingerprint/{id}',    [AttendanceController::class, 'updateFingerprint']);
    Route::delete('/fingerprint/{id}', [AttendanceController::class, 'deleteFingerprint']);

    // ── SCAN depuis Arduino (entrée / sortie) ─────────────────────────────
    // Format : POST { fingerprint_index, date, time }
    // Laravel détermine automatiquement entry ou exit
    // et calcule présent / retard léger / retard grave / absent
    Route::post('/scan',         [AttendanceController::class, 'scan']);
    Route::post('/close-course',       [AttendanceController::class, 'closeCourse']);
    Route::get('/student-profile/{id}', [AttendanceController::class, 'studentProfile']);
    Route::get('/live-stream',           [AttendanceController::class, 'liveStream']);
    Route::get('/live-course',           [AttendanceController::class, 'liveCourse']);

    // Historique des scans d'un étudiant (debug / affichage détaillé)
    Route::get('/scan-history', [AttendanceController::class, 'scanHistory']);

});
