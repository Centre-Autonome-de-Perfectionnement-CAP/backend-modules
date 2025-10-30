<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\Inscription\Http\Controllers\PendingStudentController;
use App\Modules\Inscription\Http\Controllers\SubmissionController;
use App\Modules\Inscription\Http\Controllers\DossierSubmissionController;
use App\Modules\Inscription\Http\Controllers\AcademicYearController;
use App\Modules\Inscription\Http\Controllers\StudentIdController;
use App\Modules\Inscription\Http\Controllers\CycleController;
use App\Modules\Inscription\Http\Controllers\EntryDiplomaController;
use App\Modules\Inscription\Http\Controllers\PublicReferenceController;
use App\Modules\Inscription\Http\Controllers\DashboardController;


Route::prefix('api/inscription')->group(function () {

    Route::prefix('pending-students')->group(function () {
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/', [PendingStudentController::class, 'index']);
            Route::get('/{pendingStudent}', [PendingStudentController::class, 'show']);
            Route::put('/{pendingStudent}', [PendingStudentController::class, 'update']);
            Route::delete('/{pendingStudent}', [PendingStudentController::class, 'destroy']);
            Route::get('/{pendingStudent}/documents', [PendingStudentController::class, 'getDocuments']);
            Route::patch('/{pendingStudent}/financial-status', [PendingStudentController::class, 'updateStatus']);
        });
        Route::post('/', [PendingStudentController::class, 'store']);
        Route::post('/{pendingStudent}/documents', [PendingStudentController::class, 'submitDocuments']);
    });

    Route::prefix('submissions')->group(function () {
        Route::get('/active-periods', [SubmissionController::class, 'getActiveSubmissionPeriods']);
        Route::get('/active-reclamation-periods', [SubmissionController::class, 'getActiveReclamationPeriods']);
        Route::post('/check-status', [SubmissionController::class, 'checkSubmissionStatus']);
        Route::post('/check-reclamation-status', [SubmissionController::class, 'checkReclamationStatus']);

        // Admin-only CRUD for submission periods
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [SubmissionController::class, 'store']);
            Route::put('/{submissionPeriod}', [SubmissionController::class, 'update']);
            Route::delete('/{submissionPeriod}', [SubmissionController::class, 'destroy']);
        });
    });

    Route::prefix('academic-years')->group(function () {
        // Routes publiques
        Route::get('/', [AcademicYearController::class, 'index']);
        Route::get('/{academicYear}', [AcademicYearController::class, 'show']);

        // Routes protégées
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [AcademicYearController::class, 'store']);
            Route::put('/{academicYear}', [AcademicYearController::class, 'update']);
            Route::delete('/{academicYear}', [AcademicYearController::class, 'destroy']);

            Route::post('/{academicYear}/periods', [AcademicYearController::class, 'addPeriods']);
            Route::put('/{academicYear}/periods', [AcademicYearController::class, 'extendPeriods']);
            Route::delete('/{academicYear}/periods', [AcademicYearController::class, 'deletePeriods']);
        });
    });

    Route::prefix('dossiers')->group(function () {
        Route::get('/periods', [DossierSubmissionController::class, 'getSubmissionPeriods']);
        Route::post('/licence', [DossierSubmissionController::class, 'submitLicenceDossier']);
        Route::post('/master', [DossierSubmissionController::class, 'submitMasterDossier']);
        Route::post('/ingenieur/prepa', [DossierSubmissionController::class, 'submitIngenieurPrepaDossier']);
        Route::post('/ingenieur/specialite', [DossierSubmissionController::class, 'submitIngenieurSpecialiteDossier']);
        Route::post('/complement/{trackingCode}', [DossierSubmissionController::class, 'submitComplementDossier']);
        Route::get('/{trackingCode}', [DossierSubmissionController::class, 'getDossier']);
    });

    Route::prefix('students')->group(function () {
        Route::post('/lookup-id', [StudentIdController::class, 'lookup']);
        Route::post('/assign-id', [StudentIdController::class, 'assign']);
    });

    Route::get('cycles', [CycleController::class, 'index']);
    Route::get('filieres', [CycleController::class, 'allDepartmentsWithPeriods']);
    Route::get('next-deadline', [CycleController::class, 'nextDeadline']);

    Route::prefix('public')->group(function () {
        Route::get('academic-years', [PublicReferenceController::class, 'academicYears']);
        Route::get('academic-years/department/{departmentId}', [PublicReferenceController::class, 'academicYearsForDepartment']);
        Route::get('entry-diplomas', [PublicReferenceController::class, 'entryDiplomas']);
    });

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('stats', [DashboardController::class, 'stats']);
            Route::get('graphes', [DashboardController::class, 'graphes']);
        });

}); // Fin du groupe api/inscription