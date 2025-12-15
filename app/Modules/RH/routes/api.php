<?php

use Illuminate\Support\Facades\Route;
use App\Modules\RH\Http\Controllers\ProfessorController;
use App\Modules\RH\Http\Controllers\AdminUserController;
use App\Modules\RH\Http\Controllers\GradeController;
use App\Modules\RH\Http\Controllers\SignataireController;
use App\Modules\RH\Http\Controllers\DocumentManagementController;
use App\Modules\RH\Http\Controllers\ImportantInformationController;
use App\Modules\RH\Http\Controllers\FileController;

Route::prefix('api/rh')->group(function () {
    // Routes publiques
    Route::get('important-informations', [ImportantInformationController::class, 'index']);
    Route::get('professors', [ProfessorController::class, 'index']);
    Route::get('grades', [GradeController::class, 'index']);
    Route::get('files/{file}', [FileController::class, 'viewDocument']);
    Route::get('documents', [DocumentManagementController::class, 'index']);
    
    Route::middleware('auth:sanctum')->group(function () {
        // Gestion des documents
        Route::apiResource('documents', DocumentManagementController::class)->except(['index']);
        
        // Gestion des informations importantes
        Route::get('important-informations/admin', [ImportantInformationController::class, 'indexAdmin']);
        Route::apiResource('important-informations', ImportantInformationController::class)->except(['index']);
        
        // CRUD Professeurs (sauf index qui est public)
        Route::apiResource('professors', ProfessorController::class)->only(['store', 'show', 'update', 'destroy']);
        
        Route::apiResource('admin-users', AdminUserController::class);
        Route::apiResource('signataires', SignataireController::class);

        Route::post('admin-users/{adminUser}/roles/attach', [AdminUserController::class, 'attachRole']);
        Route::post('admin-users/{adminUser}/roles/detach', [AdminUserController::class, 'detachRole']);
        Route::get('admin-users-statistics', [AdminUserController::class, 'statistics']);
        // Route::get('grades', [GradeController::class, 'index']);
        Route::get('banks', [ProfessorController::class, 'getBanks']);
        
        Route::get('roles', function () {
            return response()->json([
                'success' => true,
                'data' => \App\Modules\Stockage\Models\Role::select('id', 'name', 'slug')->get(),
            ]);
        });
    });

}); 