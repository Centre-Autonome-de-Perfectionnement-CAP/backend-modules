<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\EmploiDuTemps\Http\Controllers\BuildingController;
use App\Modules\EmploiDuTemps\Http\Controllers\RoomController;
use App\Modules\EmploiDuTemps\Http\Controllers\TimeSlotController;
use App\Modules\EmploiDuTemps\Http\Controllers\ScheduledCourseController;

Route::prefix('api/emploi-temps')->group(function () {
    
    // Buildings (Bâtiments)
    Route::apiResource('buildings', BuildingController::class);
    
    // Rooms (Salles)
    Route::apiResource('rooms', RoomController::class);
    Route::get('rooms-available', [RoomController::class, 'getAvailable']);
    
    // Time Slots (Créneaux horaires)
    Route::apiResource('time-slots', TimeSlotController::class);
    Route::get('time-slots/day/{day}', [TimeSlotController::class, 'getByDay']);
    
    // Scheduled Courses (Cours planifiés)
    Route::apiResource('scheduled-courses', ScheduledCourseController::class);
    Route::post('scheduled-courses/bulk-create', [ScheduledCourseController::class, 'bulkCreate']);
    Route::post('scheduled-courses/check-conflicts', [ScheduledCourseController::class, 'checkConflicts']);
    Route::post('scheduled-courses/{scheduledCourse}/cancel', [ScheduledCourseController::class, 'cancel']);
    Route::post('scheduled-courses/{scheduledCourse}/update-hours', [ScheduledCourseController::class, 'updateHours']);
    Route::post('scheduled-courses/{scheduledCourse}/exclude-date', [ScheduledCourseController::class, 'excludeDate']);
    Route::get('scheduled-courses/{scheduledCourse}/occurrences', [ScheduledCourseController::class, 'getOccurrences']);
    Route::post('scheduled-courses/renew-schedule', [ScheduledCourseController::class, 'renewSchedule']);
    Route::post('scheduled-courses/generate-schedule', [ScheduledCourseController::class, 'generateSchedule']);
    
    // Schedule Views (Vues d'emploi du temps)
    Route::get('schedule/class-group/{classGroupId}', [ScheduledCourseController::class, 'getByClassGroup']);
    Route::get('schedule/professor/{professorId}', [ScheduledCourseController::class, 'getByProfessor']);
    Route::get('schedule/room/{roomId}', [ScheduledCourseController::class, 'getByRoom']);
    
    // PDF Downloads (Téléchargement PDF)
    Route::get('schedule/class-group/{classGroupId}/pdf', [ScheduledCourseController::class, 'downloadClassGroupSchedulePDF']);
    Route::get('schedule/professor/{professorId}/pdf', [ScheduledCourseController::class, 'downloadProfessorSchedulePDF']);
    Route::get('schedule/room/{roomId}/pdf', [ScheduledCourseController::class, 'downloadRoomSchedulePDF']);
    
}); // Fin du groupe api/emploi-temps