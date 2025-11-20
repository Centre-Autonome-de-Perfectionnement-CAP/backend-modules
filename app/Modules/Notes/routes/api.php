<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Notes\Http\Controllers\ProfessorGradeController;
use App\Modules\Notes\Http\Controllers\AdminGradeController;

Route::prefix('api/notes')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        
        // Routes pour les professeurs
        Route::prefix('professor')->group(function () {
            Route::get('my-classes', [ProfessorGradeController::class, 'getMyClasses']);
            Route::get('programs-by-class/{class_group_id}', [ProfessorGradeController::class, 'getProgramsByClass']);
            Route::post('grade-sheet', [ProfessorGradeController::class, 'getGradeSheet']);
            Route::post('create-evaluation', [ProfessorGradeController::class, 'createEvaluation']);
            Route::put('update-grade', [ProfessorGradeController::class, 'updateGrade']);
            Route::put('set-weighting', [ProfessorGradeController::class, 'setWeighting']);
            Route::put('duplicate-grade', [ProfessorGradeController::class, 'duplicateGrade']);
            Route::post('export-grade-sheet', [ProfessorGradeController::class, 'exportGradeSheet']);
        });
        
        // Routes pour l'administration
        Route::prefix('admin')->group(function () {
            Route::get('dashboard', [AdminGradeController::class, 'dashboard']);
            Route::get('grades-by-department-level', [AdminGradeController::class, 'getGradesByDepartmentLevel']);
            Route::get('program-details/{program_id}', [AdminGradeController::class, 'getProgramDetails']);
            Route::post('export-grades-by-department', [AdminGradeController::class, 'exportGradesByDepartment']);
        });
        
        // Routes pour les décisions et PV
        Route::prefix('decisions')->group(function () {
            Route::post('export-pv-fin-annee', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'exportPVFinAnnee']);
            Route::post('export-pv-deliberation', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'exportPVDeliberation']);
            Route::post('export-recap-notes', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'exportRecapNotes']);
            Route::post('save-semester-decisions', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'saveSemesterDecisions']);
            Route::post('save-year-decisions', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'saveYearDecisions']);
        });
    });
});
