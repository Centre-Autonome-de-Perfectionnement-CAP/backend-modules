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
use App\Modules\Inscription\Http\Controllers\ClassGroupController;
use App\Modules\Inscription\Http\Controllers\StudentController;
use App\Modules\Inscription\Http\Controllers\PendingStudentExportController;

Route::prefix('api/inscription')->group(function () {

    Route::prefix('pending-students')->group(function () {
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/', [PendingStudentController::class, 'index']);
            Route::get('/{pendingStudent}', [PendingStudentController::class, 'show']);
            Route::put('/{pendingStudent}', [PendingStudentController::class, 'update']);
            Route::delete('/{pendingStudent}', [PendingStudentController::class, 'destroy']);
            Route::get('/{pendingStudent}/documents', [PendingStudentController::class, 'getDocuments']);
            Route::patch('/{pendingStudent}/financial-status', [PendingStudentController::class, 'updateStatus']);
            Route::patch('/{pendingStudent}/level', [PendingStudentController::class, 'updateLevel']);
            Route::patch('/{pendingStudent}/pieces/rename', [PendingStudentController::class, 'renamePiece']);
        });
        Route::post('/', [PendingStudentController::class, 'store']);
        Route::post('/{pendingStudent}/documents', [PendingStudentController::class, 'submitDocuments']);
    });

    Route::prefix('submissions')->group(function () {
        Route::get('/active-periods', [SubmissionController::class, 'getActiveSubmissionPeriods']);
        Route::get('/active-reclamation-periods', [SubmissionController::class, 'getActiveReclamationPeriods']);
        Route::post('/check-status', [SubmissionController::class, 'checkSubmissionStatus']);
        Route::post('/check-reclamation-status', [SubmissionController::class, 'checkReclamationStatus']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [SubmissionController::class, 'store']);
            Route::put('/{submissionPeriod}', [SubmissionController::class, 'update']);
            Route::delete('/{submissionPeriod}', [SubmissionController::class, 'destroy']);
        });
    });

    Route::prefix('academic-years')->group(function () {
        Route::get('/', [AcademicYearController::class, 'index']);
        Route::get('/{academicYear}', [AcademicYearController::class, 'show']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [AcademicYearController::class, 'store']);
            Route::put('/{academicYear}', [AcademicYearController::class, 'update']);
            Route::delete('/{academicYear}', [AcademicYearController::class, 'destroy']);

            Route::get('/{academicYear}/periods', [AcademicYearController::class, 'getPeriods']);
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
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/', [StudentController::class, 'index']);
            Route::get('/export/fiche-presence', [StudentController::class, 'exportFichePresence']);
            Route::get('/export/fiche-emargement', [StudentController::class, 'exportFicheEmargement']);
            Route::get('/{id}', [StudentController::class, 'show']);

            // ✅ AJOUT DE TA ROUTE ICI
            Route::put('/{id}/fingerprint', [StudentController::class, 'updateFingerprint']);
        });

        Route::post('/lookup-id', [StudentIdController::class, 'lookup']);
        Route::post('/assign-id', [StudentIdController::class, 'assign']);
    });

    Route::prefix('class-groups')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [ClassGroupController::class, 'index']);
        Route::get('/by-class/{classGroupId}', [ClassGroupController::class, 'getGroupsByClass']);
        Route::post('/', [ClassGroupController::class, 'store']);
        Route::post('/create-default', [ClassGroupController::class, 'createDefault']);
        Route::get('/{classGroup}', [ClassGroupController::class, 'show']);
        Route::delete('/{classGroup}', [ClassGroupController::class, 'destroy']);
        Route::post('/delete-all', [ClassGroupController::class, 'destroyAll']);
    });

    Route::get('cycles', [CycleController::class, 'index']);
    Route::get('filieres', [CycleController::class, 'allDepartmentsWithPeriods']);
    Route::get('niveaux', [CycleController::class, 'studyLevels']);
    Route::get('niveaux/all', [CycleController::class, 'allStudyLevels']);
    Route::get('cohortes', [CycleController::class, 'cohorts']);
    Route::get('next-deadline', [CycleController::class, 'nextDeadline']);

    Route::prefix('public')->group(function () {
        Route::get('academic-years', [PublicReferenceController::class, 'academicYears']);
        Route::get('academic-years/department/{departmentId}', [PublicReferenceController::class, 'academicYearsForDepartment']);
        Route::get('entry-diplomas', [PublicReferenceController::class, 'entryDiplomas']);
    });

    Route::prefix('dashboard')->middleware('auth:sanctum')->group(function () {
        Route::get('stats', [DashboardController::class, 'stats']);
        Route::get('graphes', [DashboardController::class, 'graphes']);
    });

    Route::middleware('auth:sanctum')->get('files/legacy', [\App\Modules\Inscription\Http\Controllers\FileController::class, 'viewLegacyFile']);
    Route::middleware('auth:sanctum')->post('send-mail', [\App\Modules\Inscription\Http\Controllers\MailController::class, 'sendMail']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('export/pdf', [PendingStudentExportController::class, 'exportPdf']);
        Route::get('export/excel', [PendingStudentExportController::class, 'exportExcel']);
        Route::get('export/word', [PendingStudentExportController::class, 'exportWord']);
        Route::get('export/emails', [PendingStudentExportController::class, 'exportEmails']);
    });

}); // Fin du groupe api/inscription