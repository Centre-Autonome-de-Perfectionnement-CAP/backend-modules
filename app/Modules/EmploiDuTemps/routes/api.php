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
use App\Modules\EmploiDuTemps\Http\Controllers\TextbookProfessorController;

// Routes pour le professeur (cahier de texte)
Route::middleware(['auth:sanctum'])->prefix('notes/professor/textbook')->group(function () {

    // EntrÃ©es pour un programme spÃ©cifique
    Route::get('/entries/{programId}', [TextbookProfessorController::class, 'entries']);

    // Publier/DÃ©publier une entrÃ©e
    Route::put('/publish/{entryId}', [TextbookProfessorController::class, 'publish']);
    Route::put('/unpublish/{entryId}', [TextbookProfessorController::class, 'unpublish']);
});

Route::prefix('emploi-du-temps')->group(function () {

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // SELECTS â€” donnÃ©es de rÃ©fÃ©rence pour les formulaires
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    Route::prefix('selects')->group(function () {
        Route::get('academic-years',              [SelectController::class, 'academicYears']);
        Route::get('departments',                 [SelectController::class, 'departments']);
        Route::get('class-groups',                [SelectController::class, 'classGroups']);
        Route::get('professors',                  [SelectController::class, 'professors']);
        Route::get('course-elements',             [SelectController::class, 'courseElements']);
        Route::get('programs',                    [SelectController::class, 'programs']);
        Route::get('rooms-by-building/{buildingId}', [SelectController::class, 'roomsByBuilding']);
    });

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // PDF â€” GÃ©nÃ©ration et tÃ©lÃ©chargement
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    Route::prefix('pdf')->group(function () {
        Route::post('download', [PdfController::class, 'download']);
        Route::get('preview',  [PdfController::class, 'preview']);
    });

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // Buildings
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    Route::apiResource('buildings', BuildingController::class);

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // Rooms
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    Route::apiResource('rooms', RoomController::class);
    Route::get('rooms-available', [RoomController::class, 'getAvailable']);

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // Time Slots
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    Route::apiResource('time-slots', TimeSlotController::class);
    Route::get('time-slots/day/{day}', [TimeSlotController::class, 'getByDay']);

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // Scheduled Courses
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    Route::apiResource('scheduled-courses', ScheduledCourseController::class);
    Route::post('scheduled-courses/bulk-create', [ScheduledCourseController::class, 'bulkCreate']);
    Route::post('scheduled-courses/check-conflicts', [ScheduledCourseController::class, 'checkConflicts']);    Route::post('scheduled-courses/{scheduledCourse}/cancel', [ScheduledCourseController::class, 'cancel']);
    Route::post('scheduled-courses/{scheduledCourse}/update-hours', [ScheduledCourseController::class, 'updateHours']);
    Route::post('scheduled-courses/{scheduledCourse}/exclude-date', [ScheduledCourseController::class, 'excludeDate']);
    Route::get('scheduled-courses/{scheduledCourse}/occurrences', [ScheduledCourseController::class, 'getOccurrences']);
    Route::post('scheduled-courses/renew-schedule', [ScheduledCourseController::class, 'renewSchedule']);
    Route::post('scheduled-courses/generate-schedule', [ScheduledCourseController::class, 'generateSchedule']);    Route::get('schedule/class-group/{classGroupId}/pdf', [ScheduledCourseController::class, 'downloadClassGroupSchedulePDF']);
    Route::get('schedule/professor/{professorId}/pdf', [ScheduledCourseController::class, 'downloadProfessorSchedulePDF']);
    Route::get('schedule/room/{roomId}/pdf', [ScheduledCourseController::class, 'downloadRoomSchedulePDF']);

    // ── EmploiDuTemps (implémentation alternative, module mirco-dev) ──    Route::get('emploi-du-temps-stats',              [EmploiDuTempsController::class, 'stats']);
    Route::post('emploi-du-temps/check-conflicts',   [EmploiDuTempsController::class, 'checkConflicts']);

    Route::apiResource('emploi-du-temps', EmploiDuTempsController::class);

    Route::post('emploi-du-temps/{emploiDuTemps}/cancel',     [EmploiDuTempsController::class, 'cancel']);
    Route::get('emploi-du-temps/{emploiDuTemps}/occurrences', [EmploiDuTempsController::class, 'occurrences']);
}); // Fin du groupe api/emploi-temps