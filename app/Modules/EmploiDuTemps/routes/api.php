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
    Route::post('scheduled-courses/check-conflicts', [ScheduledCourseController::class, 'checkConflicts']);
    Route::post('scheduled-courses/{scheduledCourse}/cancel', [ScheduledCourseController::class, 'cancel']);
    Route::post('scheduled-courses/{scheduledCourse}/update-hours', [ScheduledCourseController::class, 'updateHours']);
    Route::post('scheduled-courses/{scheduledCourse}/exclude-date', [ScheduledCourseController::class, 'excludeDate']);
    Route::get('scheduled-courses/{scheduledCourse}/occurrences', [ScheduledCourseController::class, 'getOccurrences']);
    
    // Schedule Views (Vues d'emploi du temps)
    Route::get('schedule/class-group/{classGroupId}', [ScheduledCourseController::class, 'getByClassGroup']);
    Route::get('schedule/professor/{professorId}', [ScheduledCourseController::class, 'getByProfessor']);
    Route::get('schedule/room/{roomId}', [ScheduledCourseController::class, 'getByRoom']);
    
}); // Fin du groupe api/emploi-temps