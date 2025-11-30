<?php

use Illuminate\Support\Facades\Route;
use App\Modules\RH\Http\Controllers\ProfessorController;
use App\Modules\RH\Http\Controllers\AdminUserController;
use App\Modules\RH\Http\Controllers\GradeController;
use App\Modules\RH\Http\Controllers\SignataireController;

Route::prefix('api/rh')->group(function () {
    Route::get('professors', [ProfessorController::class, 'index']);
    Route::get('grades', [GradeController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
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