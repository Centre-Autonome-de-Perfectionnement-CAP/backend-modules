<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\EmploiDuTemps\Http\Controllers\BuildingController;
use App\Modules\EmploiDuTemps\Http\Controllers\RoomController;
use App\Modules\EmploiDuTemps\Http\Controllers\TimeSlotController;
use App\Modules\EmploiDuTemps\Http\Controllers\ScheduledCourseController;
use App\Modules\EmploiDuTemps\Http\Controllers\EmploiDuTempsController;
use App\Modules\EmploiDuTemps\Http\Controllers\SelectController;

Route::prefix('emploi-temps')->group(function () {

    // ════════════════════════════════════════════════════════════
    // SELECTS — données de référence pour les formulaires
    // Toutes accessibles sous /api/emploi-temps/selects/...
    // ════════════════════════════════════════════════════════════

    Route::prefix('selects')->group(function () {

        // Années académiques
        // GET /api/emploi-temps/selects/academic-years
        Route::get('academic-years', [SelectController::class, 'academicYears']);

        // Départements (filières)
        // GET /api/emploi-temps/selects/departments
        Route::get('departments', [SelectController::class, 'departments']);

        // Groupes de classe (filtrables par academic_year_id et department_id)
        // GET /api/emploi-temps/selects/class-groups?academic_year_id=1&department_id=2
        Route::get('class-groups', [SelectController::class, 'classGroups']);

        // Professeurs
        // GET /api/emploi-temps/selects/professors?search=dupont
        Route::get('professors', [SelectController::class, 'professors']);

        // Éléments de cours (ECUE)
        // GET /api/emploi-temps/selects/course-elements?teaching_unit_id=3
        Route::get('course-elements', [SelectController::class, 'courseElements']);

        // Programmes (filtrables par academic_year_id + department_id)
        // GET /api/emploi-temps/selects/programs?academic_year_id=1&department_id=2
        Route::get('programs', [SelectController::class, 'programs']);

        // Salles d'un bâtiment donné
        // GET /api/emploi-temps/selects/rooms-by-building/5
        Route::get('rooms-by-building/{buildingId}', [SelectController::class, 'roomsByBuilding']);
    });

    // ════════════════════════════════════════════════════════════
    // Buildings (Bâtiments)
    // ════════════════════════════════════════════════════════════
    Route::apiResource('buildings', BuildingController::class);

    // ════════════════════════════════════════════════════════════
    // Rooms (Salles)
    // ════════════════════════════════════════════════════════════
    Route::apiResource('rooms', RoomController::class);
    Route::get('rooms-available', [RoomController::class, 'getAvailable']);

    // ════════════════════════════════════════════════════════════
    // Time Slots (Créneaux horaires)
    // ════════════════════════════════════════════════════════════
    Route::apiResource('time-slots', TimeSlotController::class);
    Route::get('time-slots/day/{day}', [TimeSlotController::class, 'getByDay']);

    // ════════════════════════════════════════════════════════════
    // Scheduled Courses (Cours planifiés)
    // ════════════════════════════════════════════════════════════
    Route::apiResource('scheduled-courses', ScheduledCourseController::class);
    Route::post('scheduled-courses/check-conflicts',          [ScheduledCourseController::class, 'checkConflicts']);
    Route::post('scheduled-courses/{scheduledCourse}/cancel', [ScheduledCourseController::class, 'cancel']);
    Route::post('scheduled-courses/{scheduledCourse}/update-hours', [ScheduledCourseController::class, 'updateHours']);
    Route::post('scheduled-courses/{scheduledCourse}/exclude-date', [ScheduledCourseController::class, 'excludeDate']);
    Route::get('scheduled-courses/{scheduledCourse}/occurrences', [ScheduledCourseController::class, 'getOccurrences']);

    // ════════════════════════════════════════════════════════════
    // Schedule Views
    // ════════════════════════════════════════════════════════════
    Route::get('schedule/class-group/{classGroupId}', [ScheduledCourseController::class, 'getByClassGroup']);
    Route::get('schedule/professor/{professorId}',    [ScheduledCourseController::class, 'getByProfessor']);
    Route::get('schedule/room/{roomId}',              [ScheduledCourseController::class, 'getByRoom']);

    // ════════════════════════════════════════════════════════════
    // Emploi du Temps — CRUD complet
    // ════════════════════════════════════════════════════════════

    // IMPORTANT: les routes nommées (check-conflicts, stats) DOIVENT être
    // déclarées AVANT apiResource pour éviter les conflits de routing Laravel.
    Route::get('emploi-du-temps-stats',                       [EmploiDuTempsController::class, 'stats']);
    Route::post('emploi-du-temps/check-conflicts',            [EmploiDuTempsController::class, 'checkConflicts']);

    Route::apiResource('emploi-du-temps', EmploiDuTempsController::class);

    Route::post('emploi-du-temps/{emploiDuTemps}/cancel',     [EmploiDuTempsController::class, 'cancel']);
    Route::get('emploi-du-temps/{emploiDuTemps}/occurrences', [EmploiDuTempsController::class, 'occurrences']);

}); // Fin du groupe api/emploi-temps