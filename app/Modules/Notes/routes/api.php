<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Notes\Http\Controllers\ProfessorGradeController;
use App\Modules\Notes\Http\Controllers\AdminGradeController;
use App\Modules\Notes\Http\Controllers\CourseRetakeController;

Route::prefix('api/notes')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        
        // Routes pour les professeurs
        Route::prefix('professor')->group(function () {
            Route::get('my-classes', [ProfessorGradeController::class, 'getMyClasses']);
            Route::get('programs-by-class/{class_group_id}', [ProfessorGradeController::class, 'getProgramsByClass']);
            Route::get('grade-sheet/{program}', [ProfessorGradeController::class, 'getGradeSheet']);
            Route::get('students-for-evaluation/{program}', [ProfessorGradeController::class, 'getStudentsForEvaluation']);
            Route::post('create-evaluation', [ProfessorGradeController::class, 'createEvaluation']);
            Route::put('update-grade', [ProfessorGradeController::class, 'updateGrade']);
            Route::put('set-weighting', [ProfessorGradeController::class, 'setWeighting']);
            Route::put('duplicate-grade', [ProfessorGradeController::class, 'duplicateGrade']);
            Route::post('delete-evaluation', [ProfessorGradeController::class, 'deleteEvaluation']);
            Route::get('export-grade-sheet/{program}', [ProfessorGradeController::class, 'exportGradeSheet']);
        });
        
        // Routes pour l'administration
        Route::prefix('admin')->group(function () {
            Route::get('dashboard', [AdminGradeController::class, 'dashboard']);
            Route::get('grades-by-department-level', [AdminGradeController::class, 'getGradesByDepartmentLevel']);
            Route::get('program-details/{program_id}', [AdminGradeController::class, 'getProgramDetails']);
            Route::post('export-grades-by-department', [AdminGradeController::class, 'exportGradesByDepartment']);
        });
        
        // Routes pour les reprises
        Route::prefix('retakes')->group(function () {
            Route::get('my-retakes', [CourseRetakeController::class, 'getStudentRetakes']);
            Route::put('{retake_id}/status', [CourseRetakeController::class, 'updateRetakeStatus']);
        });
        
        // Routes pour les décisions et PV
        Route::prefix('decisions')->group(function () {
            Route::get('students-by-semester', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'getStudentsBySemester']);
            Route::get('students-by-year', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'getStudentsByYear']);
            Route::get('export-pv-fin-annee', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'exportPVFinAnnee']);
            Route::get('export-pv-deliberation', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'exportPVDeliberation']);
            Route::get('export-recap-notes', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'exportRecapNotes']);
            Route::post('save-semester-decisions', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'saveSemesterDecisions']);
            Route::post('save-year-decisions', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'saveYearDecisions']);
        });
    });
});
