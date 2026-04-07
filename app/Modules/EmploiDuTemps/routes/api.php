<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\EmploiDuTemps\Http\Controllers\BuildingController;
use App\Modules\EmploiDuTemps\Http\Controllers\RoomController;
use App\Modules\EmploiDuTemps\Http\Controllers\TimeSlotController;
use App\Modules\EmploiDuTemps\Http\Controllers\ScheduledCourseController;
use App\Modules\EmploiDuTemps\Http\Controllers\EmploiDuTempsController;
use App\Modules\EmploiDuTemps\Http\Controllers\SelectController;
use App\Modules\EmploiDuTemps\Http\Controllers\PdfController;

Route::prefix('emploi-temps')->group(function () {

    // ════════════════════════════════════════════════════════════
    // SELECTS — données de référence pour les formulaires
    // ════════════════════════════════════════════════════════════
    Route::prefix('selects')->group(function () {
        Route::get('academic-years',              [SelectController::class, 'academicYears']);
        Route::get('departments',                 [SelectController::class, 'departments']);
        Route::get('class-groups',                [SelectController::class, 'classGroups']);
        Route::get('professors',                  [SelectController::class, 'professors']);
        Route::get('course-elements',             [SelectController::class, 'courseElements']);
        Route::get('programs',                    [SelectController::class, 'programs']);
        Route::get('rooms-by-building/{buildingId}', [SelectController::class, 'roomsByBuilding']);
    });

    // ════════════════════════════════════════════════════════════
    // PDF — Génération et téléchargement
    // POST /api/emploi-temps/pdf/download
    // ════════════════════════════════════════════════════════════
    Route::prefix('pdf')->group(function () {
        Route::post('download', [PdfController::class, 'download']);
        Route::get('preview',  [PdfController::class, 'preview']);
    });

    // ════════════════════════════════════════════════════════════
    // Buildings
    // ════════════════════════════════════════════════════════════
    Route::apiResource('buildings', BuildingController::class);

    // ════════════════════════════════════════════════════════════
    // Rooms
    // ════════════════════════════════════════════════════════════
    Route::apiResource('rooms', RoomController::class);
    Route::get('rooms-available', [RoomController::class, 'getAvailable']);

    // ════════════════════════════════════════════════════════════
    // Time Slots
    // ════════════════════════════════════════════════════════════
    Route::apiResource('time-slots', TimeSlotController::class);
    Route::get('time-slots/day/{day}', [TimeSlotController::class, 'getByDay']);

    // ════════════════════════════════════════════════════════════
    // Scheduled Courses
    // ════════════════════════════════════════════════════════════
    Route::apiResource('scheduled-courses', ScheduledCourseController::class);
    Route::post('scheduled-courses/check-conflicts',          [ScheduledCourseController::class, 'checkConflicts']);
    Route::post('scheduled-courses/{scheduledCourse}/cancel', [ScheduledCourseController::class, 'cancel']);
    Route::post('scheduled-courses/{scheduledCourse}/update-hours', [ScheduledCourseController::class, 'updateHours']);
    Route::post('scheduled-courses/{scheduledCourse}/exclude-date', [ScheduledCourseController::class, 'excludeDate']);
    Route::get('scheduled-courses/{scheduledCourse}/occurrences', [ScheduledCourseController::class, 'getOccurrences']);

    // Schedule Views
    Route::get('schedule/class-group/{classGroupId}', [ScheduledCourseController::class, 'getByClassGroup']);
    Route::get('schedule/professor/{professorId}',    [ScheduledCourseController::class, 'getByProfessor']);
    Route::get('schedule/room/{roomId}',              [ScheduledCourseController::class, 'getByRoom']);

    // ════════════════════════════════════════════════════════════
    // Emploi du Temps — CRUD
    // IMPORTANT: routes nommées AVANT apiResource
    // ════════════════════════════════════════════════════════════
    Route::get('emploi-du-temps-stats',              [EmploiDuTempsController::class, 'stats']);
    Route::post('emploi-du-temps/check-conflicts',   [EmploiDuTempsController::class, 'checkConflicts']);

    Route::apiResource('emploi-du-temps', EmploiDuTempsController::class);

    Route::post('emploi-du-temps/{emploiDuTemps}/cancel',     [EmploiDuTempsController::class, 'cancel']);
    Route::get('emploi-du-temps/{emploiDuTemps}/occurrences', [EmploiDuTempsController::class, 'occurrences']);

}); // Fin du groupe api/emploi-temps