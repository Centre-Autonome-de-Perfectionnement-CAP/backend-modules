<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Attendance\Http\Controllers\AttendanceController;

Route::prefix('attendance')->group(function () {

    Route::get('/filters',    [AttendanceController::class, 'getFilters']);
    Route::get('/dashboard',  [AttendanceController::class, 'dashboard']);

    Route::get('/management',        [AttendanceController::class, 'management']);
    Route::get('/management/export', [AttendanceController::class, 'export']);

    // Séances disponibles pour un cours donné (dates distinctes)
    Route::get('/sessions', [AttendanceController::class, 'sessions']);

    Route::get('/course-attendance',        [AttendanceController::class, 'courseAttendance']);
    Route::get('/course-attendance/export', [AttendanceController::class, 'exportCourseAttendance']);

    Route::get('/sensor-status', [AttendanceController::class, 'sensorStatus']);

    // fingerprint/export AVANT fingerprint/{id}
    Route::get('/fingerprint',         [AttendanceController::class, 'fingerprint']);
    Route::post('/fingerprint',        [AttendanceController::class, 'storeFingerprint']);
    Route::get('/fingerprint/export',  [AttendanceController::class, 'exportFingerprint']);
    Route::put('/fingerprint/{id}',    [AttendanceController::class, 'updateFingerprint']);
    Route::delete('/fingerprint/{id}', [AttendanceController::class, 'deleteFingerprint']);

    // Scan depuis Arduino
    Route::post('/scan', [AttendanceController::class, 'scan']);
});
