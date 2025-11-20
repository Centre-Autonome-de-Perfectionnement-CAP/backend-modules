<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Notes\Http\Controllers\ProfessorGradeController;
use App\Modules\Notes\Http\Controllers\AdminGradeController;
<<<<<<< HEAD
use App\Modules\Notes\Http\Controllers\CourseRetakeController;
use App\Modules\Notes\Http\Controllers\PublicGradeController;

// Routes publiques pour consultation des résultats par les étudiants
Route::prefix('api/public/grades')->group(function () {
    Route::post('authenticate', [PublicGradeController::class, 'authenticate']);
    Route::post('results', [PublicGradeController::class, 'getResults']);
});
=======
>>>>>>> eea2b06 (draft)

Route::prefix('api/notes')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        
        // Routes pour les professeurs
        Route::prefix('professor')->group(function () {
            Route::get('my-classes', [ProfessorGradeController::class, 'getMyClasses']);
            Route::get('programs-by-class/{class_group_id}', [ProfessorGradeController::class, 'getProgramsByClass']);
<<<<<<< HEAD
            Route::get('grade-sheet/{program}', [ProfessorGradeController::class, 'getGradeSheet']);
            Route::get('students-for-evaluation/{program}', [ProfessorGradeController::class, 'getStudentsForEvaluation']);
=======
            Route::post('grade-sheet', [ProfessorGradeController::class, 'getGradeSheet']);
>>>>>>> eea2b06 (draft)
            Route::post('create-evaluation', [ProfessorGradeController::class, 'createEvaluation']);
            Route::put('update-grade', [ProfessorGradeController::class, 'updateGrade']);
            Route::put('set-weighting', [ProfessorGradeController::class, 'setWeighting']);
            Route::put('duplicate-grade', [ProfessorGradeController::class, 'duplicateGrade']);
<<<<<<< HEAD
            Route::post('delete-evaluation', [ProfessorGradeController::class, 'deleteEvaluation']);
            Route::get('export-grade-sheet/{program}', [ProfessorGradeController::class, 'exportGradeSheet']);
=======
            Route::post('export-grade-sheet', [ProfessorGradeController::class, 'exportGradeSheet']);
>>>>>>> eea2b06 (draft)
        });
        
        // Routes pour l'administration
        Route::prefix('admin')->group(function () {
            Route::get('dashboard', [AdminGradeController::class, 'dashboard']);
            Route::get('grades-by-department-level', [AdminGradeController::class, 'getGradesByDepartmentLevel']);
            Route::get('program-details/{program_id}', [AdminGradeController::class, 'getProgramDetails']);
            Route::post('export-grades-by-department', [AdminGradeController::class, 'exportGradesByDepartment']);
        });
        
<<<<<<< HEAD
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
=======
        // Routes pour les décisions et PV
        Route::prefix('decisions')->group(function () {
            Route::post('export-pv-fin-annee', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'exportPVFinAnnee']);
            Route::post('export-pv-deliberation', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'exportPVDeliberation']);
            Route::post('export-recap-notes', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'exportRecapNotes']);
>>>>>>> eea2b06 (draft)
            Route::post('save-semester-decisions', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'saveSemesterDecisions']);
            Route::post('save-year-decisions', [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'saveYearDecisions']);
        });
    });
});
