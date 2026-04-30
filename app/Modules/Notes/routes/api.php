<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Notes\Http\Controllers\ProfessorGradeController;
use App\Modules\Notes\Http\Controllers\AdminGradeController;
use App\Modules\Notes\Http\Controllers\CourseRetakeController;
use App\Modules\Notes\Http\Controllers\PublicGradeController;
use App\Modules\Notes\Http\Controllers\ProfessorTextbookController;

// ─────────────────────────────────────────────────────────────────────────────
// Routes publiques pour consultation des résultats par les étudiants
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('public/grades')->group(function () {
    Route::post('authenticate', [PublicGradeController::class, 'authenticate']);
    Route::post('results',      [PublicGradeController::class, 'getResults']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Toutes les routes du module Notes
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('notes')->middleware('auth:sanctum')->group(function () {

    // ── Professeur : Notes / Évaluations ──────────────────────────────────
    Route::prefix('professor')->group(function () {
        Route::get('my-classes',                          [ProfessorGradeController::class, 'getMyClasses']);
        Route::get('programs-by-class/{class_group_id}',  [ProfessorGradeController::class, 'getProgramsByClass']);
        Route::get('grade-sheet/{program}',               [ProfessorGradeController::class, 'getGradeSheet']);
        Route::get('students-for-evaluation/{program}',   [ProfessorGradeController::class, 'getStudentsForEvaluation']);
        Route::post('create-evaluation',                  [ProfessorGradeController::class, 'createEvaluation']);
        Route::put('update-grade',                        [ProfessorGradeController::class, 'updateGrade']);
        Route::put('set-weighting',                       [ProfessorGradeController::class, 'setWeighting']);
        Route::put('duplicate-grade',                     [ProfessorGradeController::class, 'duplicateGrade']);
        Route::post('delete-evaluation',                  [ProfessorGradeController::class, 'deleteEvaluation']);
        Route::get('export-grade-sheet/{program}',        [ProfessorGradeController::class, 'exportGradeSheet']);

        Route::prefix('textbook')->group(function () {
            // Statistiques globales
            Route::get('stats',                       [ProfessorTextbookController::class, 'getStats']);
            // Liste des programmes pour le <select>
            Route::get('programs',                    [ProfessorTextbookController::class, 'getPrograms']);
            // Historique des entrées d'un programme
            Route::get('{programId}/entries',         [ProfessorTextbookController::class, 'getEntries']);
            // Publier une entrée (draft → published)
            Route::put('entries/{entryId}/publish', [ProfessorTextbookController::class, 'publishEntry']);
            // Dépublier une entrée (published → draft)
            Route::put('entries/{entryId}/unpublish', [ProfessorTextbookController::class, 'unpublishEntry']);
        });
    });

    // ── Administration ─────────────────────────────────────────────────────
    Route::prefix('admin')->group(function () {
        Route::get('dashboard',                    [AdminGradeController::class, 'dashboard']);
        Route::get('grades-by-department-level',   [AdminGradeController::class, 'getGradesByDepartmentLevel']);
        Route::get('program-details/{program_id}', [AdminGradeController::class, 'getProgramDetails']);
        Route::post('export-grades-by-department', [AdminGradeController::class, 'exportGradesByDepartment']);
    });

    // ── Reprises ───────────────────────────────────────────────────────────
    Route::prefix('retakes')->group(function () {
        Route::get('my-retakes',            [CourseRetakeController::class, 'getStudentRetakes']);
        Route::put('{retake_id}/status',    [CourseRetakeController::class, 'updateRetakestatus']);
    });

    // ── Décisions et PV ────────────────────────────────────────────────────
    Route::prefix('decisions')->group(function () {
        Route::get('students-by-semester',    [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'getStudentsBySemester']);
        Route::get('students-by-year',        [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'getStudentsByYear']);
        Route::get('export-pv-fin-annee',     [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'exportPVFinAnnee']);
        Route::get('export-pv-deliberation',  [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'exportPVDeliberation']);
        Route::get('export-recap-notes',      [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'exportRecapNotes']);
        Route::post('save-semester-decisions',[\App\Modules\Notes\Http\Controllers\DecisionController::class, 'saveSemesterDecisions']);
        Route::post('save-year-decisions',    [\App\Modules\Notes\Http\Controllers\DecisionController::class, 'saveYearDecisions']);
    });
});