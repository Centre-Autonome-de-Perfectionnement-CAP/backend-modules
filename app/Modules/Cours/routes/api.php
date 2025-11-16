<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Cours\Http\Controllers\TeachingUnitController;
use App\Modules\Cours\Http\Controllers\CourseElementController;
use App\Modules\Cours\Http\Controllers\CourseElementResourceController;
use App\Modules\Cours\Http\Controllers\CourseElementProfessorController;
use App\Modules\Cours\Http\Controllers\ProgramController;

Route::prefix('api/cours')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        // Teaching Units (Unités d'Enseignement - UE)
        Route::apiResource('teaching-units', TeachingUnitController::class);
        Route::get('teaching-units/{teachingUnit}/course-elements', [TeachingUnitController::class, 'getCourseElements']);
        
        // Course Elements (Éléments Constitutifs d'UE - ECUE)
        Route::apiResource('course-elements', CourseElementController::class);
        Route::post('course-elements/{courseElement}/professors/attach', [CourseElementController::class, 'attachProfessor']);
        Route::post('course-elements/{courseElement}/professors/detach', [CourseElementController::class, 'detachProfessor']);
        Route::get('course-elements/{courseElement}/professors', [CourseElementController::class, 'getProfessors']);
        Route::get('course-elements/{courseElement}/resources', [CourseElementController::class, 'getResources']);
        
        // Course Resources (Ressources Pédagogiques)
        Route::apiResource('course-resources', CourseElementResourceController::class);
        
        // Course Element Professor Assignments (Associations Matière-Professeur)
        Route::apiResource('course-element-professors', CourseElementProfessorController::class);
        Route::get('course-elements/{courseElement}/assignments', [CourseElementProfessorController::class, 'getByCourseElement']);
        Route::post('course-element-professors/renew', [CourseElementProfessorController::class, 'renewForNextYear']);
        
        // Programs (Emploi du temps / Assignations)
        Route::apiResource('programs', ProgramController::class);
        Route::post('programs/bulk', [ProgramController::class, 'bulkStore']);
        Route::post('programs/copy', [ProgramController::class, 'copyPrograms']);
        Route::post('programs/renew', [ProgramController::class, 'renewForNextYear']);
<<<<<<< HEAD
        Route::post('programs/{program}/renew', [ProgramController::class, 'renewProgram']);
=======
>>>>>>> f355611 (draft)
        
        // Routes utilitaires pour les programmes
        Route::get('class-groups/{classGroupId}/programs', [ProgramController::class, 'getByClassGroup']);
        Route::get('professors/{professorId}/programs', [ProgramController::class, 'getByProfessor']);
        Route::get('course-elements/{courseElementId}/programs', [ProgramController::class, 'getByCourseElement']);
        
        // Route pour récupérer toutes les classes
        Route::get('class-groups', [ProgramController::class, 'getClassGroups']);
    });
});
