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


use App\Modules\Inscription\Http\Controllers\StudentBroadcastController;


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
    
    // Broadcast messages aux étudiants
    Route::middleware('auth:sanctum')->post('broadcast/whatsapp-invitation', [StudentBroadcastController::class, 'sendWhatsAppInvitation']);
    Route::middleware('auth:sanctum')->get('broadcast/whatsapp-status/{broadcastId}', [StudentBroadcastController::class, 'getBroadcastStatus']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('export/pdf', [PendingStudentExportController::class, 'exportPdf']);
        Route::get('export/excel', [PendingStudentExportController::class, 'exportExcel']);
        Route::get('export/word', [PendingStudentExportController::class, 'exportWord']);
        Route::get('export/emails', [PendingStudentExportController::class, 'exportEmails']);
        Route::get('export/validated-students', [PendingStudentExportController::class, 'exportValidatedStudents']);
    });


    // ─── Corrections d'informations personnelles ──────────────────────────────

    Route::prefix('corrections')->group(function () {
        // Routes publiques (site vitrine)
        Route::post('/lookup', [\App\Modules\Inscription\Http\Controllers\InformationCorrectionController::class, 'lookup']);
        Route::post('/', [\App\Modules\Inscription\Http\Controllers\InformationCorrectionController::class, 'store']);
        Route::get('/status/{matricule}', [\App\Modules\Inscription\Http\Controllers\InformationCorrectionController::class, 'studentStatus']);

        // Routes admin (protégées Sanctum)
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/', [\App\Modules\Inscription\Http\Controllers\InformationCorrectionController::class, 'index']);
            Route::patch('/{id}/approve', [\App\Modules\Inscription\Http\Controllers\InformationCorrectionController::class, 'approve']);
            Route::patch('/{id}/reject', [\App\Modules\Inscription\Http\Controllers\InformationCorrectionController::class, 'reject']);
        });
    });

}); // Fin du groupe api/inscription