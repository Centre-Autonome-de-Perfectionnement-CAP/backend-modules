<?php

use Illuminate\Support\Facades\Route;
use App\Modules\RH\Http\Controllers\ProfessorController;
use App\Modules\RH\Http\Controllers\AdminUserController;
use App\Modules\RH\Http\Controllers\GradeController;
use App\Modules\RH\Http\Controllers\SignataireController;
use App\Modules\RH\Http\Controllers\DocumentManagementController;
use App\Modules\RH\Http\Controllers\ImportantInformationController;
use App\Modules\RH\Http\Controllers\FileController;
use App\Modules\RH\Http\Controllers\ContratController;
use App\Modules\RH\Http\Controllers\AcademicYearController;
use App\Modules\RH\Http\Controllers\CycleController;

Route::prefix('rh')->group(function () {

    Route::get('professors',           [ProfessorController::class, 'index']);
    Route::get('grades',               [GradeController::class, 'index']);
    Route::get('files/{file}',         [FileController::class, 'viewDocument']);
    Route::get('documents',            [DocumentManagementController::class, 'index']);
    Route::get('important-informations', [ImportantInformationController::class, 'index']);
    Route::get('academic-years',       [AcademicYearController::class, 'index']);
    Route::get('cycles',               [CycleController::class, 'index']);

    // ─── Contrats (CRUD complet) ──────────────────────────────────────────────
    Route::get('contrats',         [ContratController::class, 'index']);
    Route::post('contrats',        [ContratController::class, 'store']);
    Route::get('contrats/{id}',    [ContratController::class, 'show']);
    Route::put('contrats/{id}',    [ContratController::class, 'update']);
    Route::delete('contrats/{id}', [ContratController::class, 'destroy']);

    // ─── Programmes d'un professeur (matières + classes assignées) ────────────
    // Utilisé pour pré-remplir le select "Programmes" dans le formulaire de contrat
    Route::get('professors/{professorId}/programs', [ContratController::class, 'professorPrograms']);

    // ─── Routes protégées ─────────────────────────────────────────────────────

    Route::apiResource('documents', DocumentManagementController::class)->except(['index']);

    Route::get('important-informations/admin', [ImportantInformationController::class, 'indexAdmin']);
    Route::apiResource('important-informations', ImportantInformationController::class)->except(['index']);

    Route::apiResource('professors', ProfessorController::class)->only(['store', 'show', 'update', 'destroy']);

    Route::apiResource('admin-users', AdminUserController::class);
    Route::post('admin-users/{adminUser}/roles/attach', [AdminUserController::class, 'attachRole']);
    Route::post('admin-users/{adminUser}/roles/detach', [AdminUserController::class, 'detachRole']);
    Route::get('admin-users-statistics', [AdminUserController::class, 'statistics']);

    Route::apiResource('signataires', SignataireController::class);

    Route::get('banks', [ProfessorController::class, 'getBanks']);

    Route::post('contrats/{id}/send-transfer-email', [ContratController::class, 'sendTransferEmail']);
    Route::get('roles', function () {
        return response()->json([
            'success' => true,
            'data'    => \App\Modules\Stockage\Models\Role::select('id', 'name', 'slug')->get(),
        ]);
    });
});
